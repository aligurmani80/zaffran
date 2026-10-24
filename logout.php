<?php
/**
 * Customer Logout Action
 * Destroys customer session securely and redirects to login or homepage.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

CustomerAuth::logout();

header('Location: login.php');
exit;
