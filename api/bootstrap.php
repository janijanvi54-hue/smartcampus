<?php
/**
 * API bootstrap - JSON responses, CORS-friendly, JSON input helpers.
 * Require this at the top of every endpoint in /api.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function api_require_login(): array {
    $u = current_user();
    if (!$u) json_response(['error' => 'Not authenticated'], 401);
    return $u;
}

function api_require_role(string $role): array {
    $u = api_require_login();
    if ($u['role'] !== $role) json_response(['error' => 'Forbidden'], 403);
    return $u;
}

/** Reusable "paginate + sort" helpers for list endpoints. */
function api_page_params(): array {
    return [
        'page'  => max(1, (int)($_GET['page'] ?? 1)),
        'limit' => min(200, max(1, (int)($_GET['limit'] ?? 50))),
    ];
}
