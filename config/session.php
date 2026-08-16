<?php
/**
 * SmartCampus - secure session management
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Recommended cookie flags for a production-like session
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,      // enable ('true') when serving over HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('smartcampus_session');
    session_start();

    // Absolute session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Get / set a CSRF token in the session.
 */
function csrf_token(): string {
    if (empty($_SESSION[CSRF_KEY])) {
        $_SESSION[CSRF_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_KEY];
}

/**
 * Verify a submitted CSRF token.
 */
function csrf_verify(?string $token): bool {
    return is_string($token) && $token !== '' && hash_equals($_SESSION[CSRF_KEY] ?? '', $token);
}
