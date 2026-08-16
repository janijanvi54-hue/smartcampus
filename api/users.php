<?php
/**
 * /api/users.php  (admin only)
 * GET  ?search=&role=&status=&limit=  -> list users with booking counts
 * POST { action: 'toggle', user_id } | { action: 'update', user_id, fields... }
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search  = trim($_GET['search'] ?? '');
    $role    = $_GET['role'] ?? '';
    $status  = $_GET['status'] ?? '';
    $limit   = min(500, max(1, (int)($_GET['limit'] ?? 100)));

    $sql = "SELECT u.*,
                   (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS total_bookings,
                   (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'pending') AS pending_bookings
            FROM users u";
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = "(u.name LIKE :s OR u.email LIKE :s2 OR u.user_identifier LIKE :s3 OR u.department LIKE :s4)";
        $like = "%{$search}%";
        $params[':s'] = $like;
        $params[':s2'] = $like;
        $params[':s3'] = $like;
        $params[':s4'] = $like;
    }
    if ($role !== '' && in_array($role, ['student', 'faculty', 'admin'], true)) {
        $where[] = "u.role = :r";
        $params[':r'] = $role;
    }
    if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
        $where[] = "u.status = :st";
        $params[':st'] = $status;
    }
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY u.role, u.name LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) unset($r['password']);

    json_response(['success' => true, 'count' => count($rows), 'users' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_input();
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $name = trim($body['name'] ?? '');
        $email = trim(strtolower($body['email'] ?? ''));
        $role = $body['role'] ?? 'student';
        $department = trim($body['department'] ?? '');
        $identifier = trim($body['identifier'] ?? '');
        $password = (string)($body['password'] ?? '');

        if (mb_strlen($name) < 3) json_response(['error' => 'Name must be at least 3 characters.'], 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Invalid email address.'], 422);
        if (!in_array($role, ['student', 'faculty', 'admin'], true)) json_response(['error' => 'Invalid role.'], 422);
        if (mb_strlen($password) < 8) json_response(['error' => 'Temporary password must be at least 8 characters.'], 422);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        if ($stmt->fetch()) json_response(['error' => 'A user with this email already exists.'], 422);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, department, user_identifier, status)
                               VALUES (:n, :e, :p, :r, :d, :i, 'active')");
        $stmt->execute([
            ':n' => $name, ':e' => $email, ':p' => password_hash($password, PASSWORD_DEFAULT),
            ':r' => $role, ':d' => $department, ':i' => $identifier,
        ]);
        $newId = (int)$pdo->lastInsertId();
        send_notification($pdo, $newId, 'Account created',
            "An administrator created your SmartCampus account ({$role}). Please log in and change your password.", 'info');
        json_response(['success' => true, 'message' => 'User created successfully.'], 201);
    }

    if ($action === 'toggle') {
        $id = (int)($body['user_id'] ?? 0);
        if ($id === (int)$u['id']) json_response(['error' => 'You cannot deactivate your own account.'], 422);
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cur = $stmt->fetchColumn();
        if ($cur === false) json_response(['error' => 'User not found.'], 404);

        $new = $cur === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = :s WHERE id = :id");
        $stmt->execute([':s' => $new, ':id' => $id]);

        send_notification($pdo, $id, 'Account status changed',
            "Your SmartCampus account has been " . ($new === 'active' ? 'activated' : 'deactivated') . " by an administrator.", 'info');
        json_response(['success' => true, 'message' => 'User ' . ($new === 'active' ? 'activated' : 'deactivated') . '.']);
    }

    if ($action === 'update') {
        $id = (int)($body['user_id'] ?? 0);
        $name = trim($body['name'] ?? '');
        $department = trim($body['department'] ?? '');
        $identifier = trim($body['identifier'] ?? '');
        if ($name === '') json_response(['error' => 'Name is required.'], 400);

        $stmt = $pdo->prepare("UPDATE users SET name = :n, department = :d, user_identifier = :i WHERE id = :id");
        $stmt->execute([':n' => $name, ':d' => $department, ':i' => $identifier, ':id' => $id]);
        json_response(['success' => true, 'message' => 'User updated.']);
    }

    json_response(['error' => 'Unknown action.'], 400);
}

json_response(['error' => 'Method not allowed'], 405);
