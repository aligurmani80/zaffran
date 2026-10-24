<?php
/**
 * SMTP & Test Mailer Service for Zaffran
 * Supports real secure SMTP with explicit TLS encryption as well as
 * an isolated test transport that writes to logs/emails/ for verification.
 */

if (!defined('ZAFFRAN_APP')) {
    exit('Direct access not permitted');
}

class ZaffranMailer {

    /**
     * Send OTP email to customer
     * Returns array ['success' => bool, 'message' => string, 'mode' => string]
     */
    public static function sendOtpEmail(string $recipientEmail, string $otpCode): array {
        $mailDriver = strtolower(env('MAIL_DRIVER', 'test'));
        $fromEmail = env('SMTP_FROM_EMAIL', 'concierge@zaffran.local');
        $fromName = env('SMTP_FROM_NAME', 'Zaffran Concierge');
        $subject = 'Your Zaffran Verification Code: ' . $otpCode;

        $htmlBody = self::renderOtpTemplate($otpCode);
        $plainBody = "Hello,\n\nYour verification code for Zaffran is: {$otpCode}\n\nThis code expires in 5 minutes.\n\nIf you did not request this code, you can safely ignore this email.\n\nWarm regards,\nZaffran Team\n" . env('APP_URL', 'http://localhost/zaffran');

        // If configured for TEST mode (safe local verification without live SMTP credentials)
        if ($mailDriver === 'test') {
            $logDir = dirname(__DIR__) . '/logs/emails';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $filename = $logDir . '/email_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.txt';
            $logContent = "========================================================\n"
                        . "DATE: " . date('Y-m-d H:i:s') . "\n"
                        . "TO: {$recipientEmail}\n"
                        . "FROM: {$fromName} <{$fromEmail}>\n"
                        . "SUBJECT: {$subject}\n"
                        . "OTP CODE: {$otpCode}\n"
                        . "--------------------------------------------------------\n"
                        . $plainBody . "\n"
                        . "========================================================\n";
            @file_put_contents($filename, $logContent);

            return [
                'success' => true,
                'message' => 'Verification code sent to your email.',
                'mode' => 'test',
                'preview_file' => $filename
            ];
        }

        // Live SMTP Transport
        $smtpHost = env('SMTP_HOST', '');
        $smtpPort = (int) env('SMTP_PORT', 587);
        $smtpUser = env('SMTP_USERNAME', '');
        $smtpPass = env('SMTP_PASSWORD', '');
        $smtpEncryption = strtolower(env('SMTP_ENCRYPTION', 'tls'));

        if (empty($smtpHost) || empty($smtpUser) || empty($smtpPass)) {
            return [
                'success' => false,
                'message' => 'Live SMTP email delivery is not configured on this server. Please contact administrator.',
                'mode' => 'smtp_missing_credentials'
            ];
        }

        // Socket-based SMTP execution with EHLO, STARTTLS, AUTH LOGIN
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ]);

            $timeout = 15;
            $socket = @stream_socket_client("tcp://{$smtpHost}:{$smtpPort}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
            if (!$socket) {
                error_log("SMTP Connection failed: {$errstr} ({$errno})");
                return ['success' => false, 'message' => 'Could not connect to SMTP mail server.'];
            }

            self::getResponse($socket); // read initial 220 banner

            // EHLO
            self::sendCommand($socket, "EHLO " . gethostname());

            // STARTTLS
            if ($smtpEncryption === 'tls') {
                self::sendCommand($socket, "STARTTLS");
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    fclose($socket);
                    return ['success' => false, 'message' => 'Failed to negotiate secure TLS connection with mail server.'];
                }
                self::sendCommand($socket, "EHLO " . gethostname());
            }

            // AUTH LOGIN
            self::sendCommand($socket, "AUTH LOGIN");
            self::sendCommand($socket, base64_encode($smtpUser));
            self::sendCommand($socket, base64_encode($smtpPass));

            // MAIL FROM & RCPT TO
            self::sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            self::sendCommand($socket, "RCPT TO:<{$recipientEmail}>");

            // DATA
            self::sendCommand($socket, "DATA");

            $headers = "MIME-Version: 1.0\r\n"
                     . "From: {$fromName} <{$fromEmail}>\r\n"
                     . "To: <{$recipientEmail}>\r\n"
                     . "Subject: {$subject}\r\n"
                     . "Content-Type: text/html; charset=UTF-8\r\n"
                     . "Date: " . date('r') . "\r\n";

            $messageData = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
            fwrite($socket, $messageData);
            self::getResponse($socket);

            self::sendCommand($socket, "QUIT");
            fclose($socket);

            return ['success' => true, 'message' => 'Verification code sent to your email.', 'mode' => 'smtp'];

        } catch (Exception $e) {
            error_log("SMTP dispatch exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to deliver email through SMTP transport.'];
        }
    }

    private static function sendCommand($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
        return self::getResponse($socket);
    }

    private static function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Render Accessible & Beautiful Responsive Botanical HTML Email
     */
    private static function renderOtpTemplate(string $otpCode): string {
        $storeSettings = getStoreSettings();
        $storeName = htmlspecialchars($storeSettings['store_name'] ?? 'Zaffran');
        $storeAddress = htmlspecialchars($storeSettings['store_address'] ?? 'Circular Road, Kot Addu, Punjab, Pakistan');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Zaffran Verification Code</title>
</head>
<body style="margin:0; padding:30px 10px; background-color:#faf7ef; font-family:'Helvetica Neue', Arial, sans-serif; color:#1c2720;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:540px; margin:0 auto; background-color:#ffffff; border-radius:18px; border:1px solid #e3ded2; overflow:hidden; box-shadow:0 8px 30px rgba(35,67,49,0.06);">
    <!-- Brand Banner -->
    <tr>
      <td style="padding:32px 36px 20px; text-align:center; background:linear-gradient(180deg, #fdfcf8 0%, #f6f1e6 100%); border-bottom:1px solid #e8e2d4;">
        <div style="font-family:Georgia, serif; font-size:24px; font-weight:700; letter-spacing:4px; color:#1b3325; margin-bottom:4px;">ZAFFRAN</div>
        <div style="font-size:10px; letter-spacing:2px; text-transform:uppercase; color:#e05324; font-weight:600;">Pure Botanicals &amp; Artisanal Fragrance</div>
      </td>
    </tr>

    <!-- Body Content -->
    <tr>
      <td style="padding:36px 36px 28px;">
        <h1 style="font-family:Georgia, serif; font-size:20px; color:#1b3325; margin-top:0; margin-bottom:14px; font-weight:600;">Sign In Verification</h1>
        <p style="font-size:15px; line-height:1.6; color:#4a5b51; margin-bottom:26px;">
          Use the six-digit code below to securely access your Zaffran customer account and orders. No password is required.
        </p>

        <!-- Big OTP Display Box -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:26px;">
          <tr>
            <td style="background-color:#f5f0e3; border:2px dashed #caa04b; border-radius:12px; padding:20px; text-align:center;">
              <div style="font-family:'Courier New', Courier, monospace; font-size:36px; font-weight:800; letter-spacing:10px; color:#1b3325;">
                {$otpCode}
              </div>
              <div style="font-size:12px; color:#8d745e; margin-top:8px; font-weight:600;">
                Valid for 5 minutes • Do not share this code with anyone
              </div>
            </td>
          </tr>
        </table>

        <p style="font-size:13px; line-height:1.6; color:#788b80; margin-bottom:0;">
          If you did not request this login code, you can safely ignore this message. Your account remains protected.
        </p>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding:20px 36px; background-color:#f0f6f2; border-top:1px solid #e2ede6; text-align:center; font-size:12px; color:#788b80; line-height:1.5;">
        <strong>{$storeName}</strong><br>
        {$storeAddress}<br>
        &copy; 2026 Zaffran. All rights reserved.
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
