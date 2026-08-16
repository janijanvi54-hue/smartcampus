<?php
/**
 * SmartCampus - authentication & authorization helpers
 * Load the current user from the session on every request.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

/** Fetch the currently logged-in user (fresh from DB) or null. */
function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare("SELECT * FROM users WHERE id = :id AND status = 'active'");
            $stmt->execute([':id' => (int)$_SESSION['user_id']]);
            $row = $stmt->fetch();
            if ($row) {
                $user = $row;
            } else {
                // user deactivated or deleted
                session_destroy();
            }
        }
    }
    return $user ?: null;
}

/** Login URL carrying the page the user was trying to open, so login can return there. */
function login_redirect_url(string $path = '/login.php'): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $current = $uri !== '' ? parse_url($uri, PHP_URL_PATH) : '';
    if ($uri === '' || $current === '/login.php' || $current === '/logout.php') {
        return $path;
    }
    $sep = strpos($path, '?') !== false ? '&' : '?';
    return $path . $sep . 'return=' . rawurlencode($uri);
}

/** Require any authenticated user. */
function require_login(string $redirectTo = '/login.php'): array {
    $u = current_user();
    if (!$u) {
        set_flash('danger', 'Please log in to continue.');
        redirect(login_redirect_url($redirectTo));
    }
    return $u;
}

/** Require an authenticated user with one of the given roles. */
function require_role(string $role, string $redirectTo = '/login.php'): array {
    $u = require_login($redirectTo);
    if ($u['role'] !== $role) {
        http_response_code(403);
        die('Access denied: you do not have permission to view this page.');
    }
    return $u;
}

/** Convenience: ensure user is logged in as any of an allowed set. */
function require_any_role(array $roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied: you do not have permission to view this page.');
    }
    return $u;
}

/** Log the current user out (secure logout). */
function do_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Default landing path for a role. */
function role_home(string $role): string {
    return match ($role) {
        'admin'   => '/admin/dashboard.php',
        'faculty' => '/faculty/dashboard.php',
        default   => '/student/dashboard.php',
    };
}
