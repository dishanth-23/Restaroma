<?php
// includes/auth.php
// Handles session start, login checks, and helper functions for user/admin auth.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate (or return) current CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Echo hidden CSRF input for forms.
 */
function csrf_input(): void
{
    $token = csrf_token();
    echo '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token on POST actions.
 */
function verify_csrf_or_die(): void
{
    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    $requestToken = $_POST['_csrf_token'] ?? '';
    if (!is_string($requestToken) || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

/**
 * Regenerate session ID to mitigate fixation attacks.
 */
function secure_session_regenerate()
{
    if (!isset($_SESSION['regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = true;
    }
}

/**
 * Check if normal user is logged in.
 */
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in.
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

/**
 * Require user login, otherwise redirect to user login page.
 */
function requireUserLogin()
{
    if (!isUserLoggedIn()) {
        header('Location: /Restaurant-System/user/login.php');
        exit;
    }
}

/**
 * Require admin login, otherwise redirect to admin login page.
 */
function requireAdminLogin()
{
    if (!isAdminLoggedIn()) {
        header('Location: /Restaurant-System/admin/login.php');
        exit;
    }
}

/**
 * Logout user.
 */
function userLogout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

/**
 * Logout admin.
 */
function adminLogout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}