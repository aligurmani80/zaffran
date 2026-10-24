<?php
/**
 * Customer Authentication & OTP Challenge Engine
 * Zaffran Passwordless Email-Only Authentication
 */

if (!defined('ZAFFRAN_APP')) {
    exit('Direct access not permitted');
}

require_once __DIR__ . '/mailer.php';

class CustomerAuth {

    /**
     * Check if client IP or email has exceeded rolling rate limits
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool {
        $pdo = getDbConnection();
        if (!$pdo) return true;

        try {
            $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
            $stmtClean = $pdo->prepare("DELETE FROM auth_rate_limits WHERE last_attempt < ?");
            $stmtClean->execute([$cutoff]);

            $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM auth_rate_limits WHERE rate_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            if ($row) {
                if ($row['attempts'] >= $maxAttempts) {
                    return false;
                }
                $stmtUpd = $pdo->prepare("UPDATE auth_rate_limits SET attempts = attempts + 1, last_attempt = ? WHERE rate_key = ?");
                $stmtUpd->execute([date('Y-m-d H:i:s'), $key]);
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO auth_rate_limits (rate_key, attempts, last_attempt) VALUES (?, 1, ?)");
                $stmtIns->execute([$key, date('Y-m-d H:i:s')]);
            }
            return true;
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Generate 6-digit secure numeric OTP and keyed hash
     */
    public static function hashOtp(string $otp, string $email): string {
        $appKey = env('APP_KEY', 'default_zaffran_botanical_key_2026');
        return hash_hmac('sha256', $email . ':' . $otp, $appKey);
    }

    /**
     * Request an Email OTP Challenge
     * Sequence: Validates email, enforces 60s cooldown, invalidates old challenges,
     * stores secure HMAC hash, sends email via ZaffranMailer, and returns challenge token.
     */
    public static function requestOtp(string $rawEmail, string $ipAddress = '', string $userAgent = ''): array {
        $email = strtolower(trim($rawEmail));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please provide a valid email address.'];
        }

        // Rate limit: Max 5 requests per IP in 5 minutes
        $ipKey = 'otp_req_ip_' . md5($ipAddress);
        if (!self::checkRateLimit($ipKey, 5, 300)) {
            return ['success' => false, 'message' => 'Too many requests from this location. Please wait a few minutes.'];
        }

        // Rate limit: Max 4 requests per Email in 10 minutes
        $emailKey = 'otp_req_email_' . md5($email);
        if (!self::checkRateLimit($emailKey, 4, 600)) {
            return ['success' => false, 'message' => 'Too many requests for this email. Please wait a few minutes before trying again.'];
        }

        $pdo = getDbConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Service temporarily unavailable.'];
        }

        try {
            $nowStr = date('Y-m-d H:i:s');
            // Check 60-second cooldown on active challenges (using PHP timestamp parity)
            $stmtActive = $pdo->prepare("SELECT id, last_sent_at, expires_at FROM customer_email_otps 
                                         WHERE email = ? AND is_used = 0 
                                         ORDER BY id DESC LIMIT 1");
            $stmtActive->execute([$email]);
            $activeChallenge = $stmtActive->fetch();

            if ($activeChallenge) {
                $lastSentTs = strtotime($activeChallenge['last_sent_at']);
                $secondsSinceLast = time() - $lastSentTs;
                $expiresTs = strtotime($activeChallenge['expires_at']);

                // If not expired and under 60 seconds
                if ($secondsSinceLast < 60 && $expiresTs > time()) {
                    $wait = 60 - $secondsSinceLast;
                    return [
                        'success' => false,
                        'message' => "Please wait {$wait} seconds before requesting a new code.",
                        'cooldown_seconds' => $wait
                    ];
                }
                // Invalidate prior challenge
                $stmtInval = $pdo->prepare("UPDATE customer_email_otps SET is_used = 1 WHERE email = ? AND is_used = 0");
                $stmtInval->execute([$email]);
            }

            // Generate cryptographically secure 6-digit OTP using random_int
            $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = self::hashOtp($otpCode, $email);
            $challengeToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes validity

            // Store Challenge Record
            $sqlIns = "INSERT INTO customer_email_otps (
                          challenge_token, email, otp_hash, expires_at, attempts, max_attempts, 
                          is_used, ip_address, user_agent, purpose, last_sent_at, created_at
                       ) VALUES (
                          ?, ?, ?, ?, 0, 5, 0, ?, ?, 'auth', ?, ?
                       )";
            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute([
                $challengeToken,
                $email,
                $otpHash,
                $expiresAt,
                substr($ipAddress, 0, 45),
                substr($userAgent, 0, 255),
                $nowStr,
                $nowStr
            ]);

            // Dispatch Email via Mailer
            $mailResult = ZaffranMailer::sendOtpEmail($email, $otpCode);
            if (!$mailResult['success']) {
                return [
                    'success' => false,
                    'message' => $mailResult['message'],
                    'challenge_token' => $challengeToken
                ];
            }

            return [
                'success' => true,
                'message' => 'A 6-digit verification code has been dispatched to your email.',
                'challenge_token' => $challengeToken,
                'email' => $email,
                'expires_in' => 300
            ];

        } catch (Exception $e) {
            error_log("OTP Challenge creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while generating your verification code.'];
        }
    }

    /**
     * Verify OTP & Authenticate Customer
     * Atomic verification with attempt counting, replay prevention, and user creation/sign-in.
     */
    public static function verifyOtp(string $challengeToken, string $enteredOtp): array {
        $enteredOtp = trim($enteredOtp);
        if (!preg_match('/^\d{6}$/', $enteredOtp)) {
            return ['success' => false, 'message' => 'Please enter a valid 6-digit code.'];
        }

        $pdo = getDbConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed.'];
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM customer_email_otps WHERE challenge_token = ? FOR UPDATE");
            $stmt->execute([$challengeToken]);
            $challenge = $stmt->fetch();

            if (!$challenge) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Verification challenge not found or expired. Please sign in again.'];
            }

            if ((int)$challenge['is_used'] === 1) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'This verification code has already been used.'];
            }

            if (strtotime($challenge['expires_at']) < time()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'This verification code has expired. Please request a new code.'];
            }

            if ((int)$challenge['attempts'] >= (int)$challenge['max_attempts']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Maximum verification attempts exceeded. Please request a new code.'];
            }

            // Verify Keyed HMAC
            $expectedHash = self::hashOtp($enteredOtp, $challenge['email']);
            if (!hash_equals($challenge['otp_hash'], $expectedHash)) {
                $stmtInc = $pdo->prepare("UPDATE customer_email_otps SET attempts = attempts + 1 WHERE id = ?");
                $stmtInc->execute([$challenge['id']]);
                $remaining = (int)$challenge['max_attempts'] - ((int)$challenge['attempts'] + 1);
                $pdo->commit();

                $warn = $remaining > 0 ? " ({$remaining} attempts remaining)" : " (Challenge locked)";
                return ['success' => false, 'message' => 'Incorrect verification code.' . $warn];
            }

            // OTP is valid! Atomically mark challenge as used
            $stmtMark = $pdo->prepare("UPDATE customer_email_otps SET is_used = 1 WHERE id = ?");
            $stmtMark->execute([$challenge['id']]);

            // Locate existing customer or create verified customer account
            $email = $challenge['email'];
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1 FOR UPDATE");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();

            if ($user) {
                if (empty($user['email_verified_at'])) {
                    $stmtUpdUser = $pdo->prepare("UPDATE users SET email_verified_at = ? WHERE id = ?");
                    $stmtUpdUser->execute([date('Y-m-d H:i:s'), $user['id']]);
                    $user['email_verified_at'] = date('Y-m-d H:i:s');
                }
            } else {
                $derivedName = ucfirst(explode('@', $email)[0]);
                $nowStr = date('Y-m-d H:i:s');
                $stmtNewUser = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, phone, account_status, password, created_at, updated_at) 
                                              VALUES (?, ?, ?, '', 'active', NULL, ?, ?)");
                $stmtNewUser->execute([$derivedName, $email, $nowStr, $nowStr, $nowStr]);
                $newId = (int)$pdo->lastInsertId();

                $user = [
                    'id' => $newId,
                    'name' => $derivedName,
                    'email' => $email,
                    'email_verified_at' => $nowStr,
                    'phone' => '',
                    'account_status' => 'active'
                ];
            }

            $pdo->commit();

            // Establish Authenticated Session
            self::loginUserSession($user);

            return [
                'success' => true,
                'message' => 'Authentication successful.',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ];

        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("OTP Verification exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error during verification. Please try again.'];
        }
    }

    /**
     * Secure Session Initialization with session fixation protection
     */
    public static function loginUserSession(array $user): void {
        initSession();
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['customer_id'] = (int)$user['id'];
        $_SESSION['customer_email'] = $user['email'];
        $_SESSION['customer_name'] = $user['name'];
        $_SESSION['customer_logged_in_at'] = time();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Check if current session has an authenticated customer
     */
    public static function isAuthenticated(): bool {
        initSession();
        return !empty($_SESSION['customer_id']) && !empty($_SESSION['customer_email']);
    }

    /**
     * Get Current Authenticated Customer details from MySQL
     */
    public static function getCustomer(): ?array {
        if (!self::isAuthenticated()) {
            return null;
        }

        $pdo = getDbConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT id, name, email, email_verified_at, phone, account_status, created_at FROM users WHERE id = ? AND account_status = 'active' LIMIT 1");
        $stmt->execute([(int)$_SESSION['customer_id']]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Log out customer cleanly
     */
    public static function logout(): void {
        initSession();
        unset($_SESSION['customer_id'], $_SESSION['customer_email'], $_SESSION['customer_name'], $_SESSION['customer_logged_in_at']);
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Customer Profile Update (Name & Phone only; email is read-only)
     */
    public static function updateProfile(int $userId, string $name, string $phone): array {
        $name = trim($name);
        $phone = trim($phone);

        if (empty($name)) {
            return ['success' => false, 'message' => 'Full name cannot be blank.'];
        }

        $pdo = getDbConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Database connection failed.'];

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$name, $phone, date('Y-m-d H:i:s'), $userId]);

            $_SESSION['customer_name'] = $name;

            return ['success' => true, 'message' => 'Your profile information has been updated.'];
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile.'];
        }
    }

    /**
     * Retrieve Authorized Orders for Customer
     */
    public static function getCustomerOrders(int $userId): array {
        $pdo = getDbConnection();
        if (!$pdo) return [];

        try {
            $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) AS item_count 
                                   FROM orders o 
                                   LEFT JOIN order_items oi ON oi.order_id = o.id 
                                   WHERE o.user_id = ? 
                                   GROUP BY o.id 
                                   ORDER BY o.id DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to fetch customer orders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve Specific Order with Strict Ownership Authorization
     */
    public static function getCustomerOrderDetails(int $userId, int $orderId): ?array {
        $pdo = getDbConnection();
        if (!$pdo) return null;

        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch();
            if (!$order) return null;

            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
            $stmtItems->execute([$orderId]);
            $order['items'] = $stmtItems->fetchAll();

            return $order;
        } catch (Exception $e) {
            error_log("Order details authorization error: " . $e->getMessage());
            return null;
        }
    }
}
