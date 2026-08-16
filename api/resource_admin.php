<?php
/**
 * /api/resource_admin.php  (admin only)
 * POST { action: 'create'|'update'|'delete'|'toggle', ...resource fields }
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);

$body = json_input();
$action = $body['action'] ?? '';

function validate_resource_input(array $body): array {
    $errors = [];
    $name = trim($body['name'] ?? '');
    $type = $body['type'] ?? '';
    $capacity = (int)($body['capacity'] ?? 0);
    $location = trim($body['location'] ?? '');

    if (mb_strlen($name) < 2) $errors[] = 'Resource name is required.';
    if (!in_array($type, array_keys(RESOURCE_TYPES), true)) $errors[] = 'Please choose a valid resource type.';
    if ($capacity <= 0) $errors[] = 'Capacity must be greater than zero.';
    if ($location === '') $errors[] = 'Location is required.';
    if (!in_array($body['bookable_by'] ?? '', ['all', 'student', 'faculty', 'admin'], true)) $errors[] = 'Please choose who can book this resource.';
    return $errors;
}

switch ($action) {
    case 'create':
        $errors = validate_resource_input($body);
        if ($errors) json_response(['error' => 'Validation failed', 'errors' => $errors], 422);

        $status = in_array($body['status'] ?? '', ['active', 'inactive', 'maintenance'], true)
            ? $body['status'] : 'active';

        $stmt = $pdo->prepare("INSERT INTO resources (name, type, capacity, location, description, facilities, bookable_by, status)
                               VALUES (:n, :t, :c, :l, :d, :f, :b, :s)");
        $stmt->execute([
            ':n' => trim($body['name']),
            ':t' => $body['type'],
            ':c' => (int)$body['capacity'],
            ':l' => trim($body['location']),
            ':d' => trim($body['description'] ?? ''),
            ':f' => trim($body['facilities'] ?? ''),
            ':b' => $body['bookable_by'],
            ':s' => $status,
        ]);
        json_response(['success' => true, 'resource_id' => (int)$pdo->lastInsertId(), 'message' => 'Resource added successfully.'], 201);

    case 'update':
        $id = (int)($body['id'] ?? 0);
        $errors = validate_resource_input($body);
        if ($errors) json_response(['error' => 'Validation failed', 'errors' => $errors], 422);

        $status = in_array($body['status'] ?? '', ['active', 'inactive', 'maintenance'], true)
            ? $body['status'] : 'active';

        $stmt = $pdo->prepare("UPDATE resources SET name = :n, type = :t, capacity = :c, location = :l, description = :d, facilities = :f, bookable_by = :b, status = :s WHERE id = :id");
        $stmt->execute([
            ':n' => trim($body['name']),
            ':t' => $body['type'],
            ':c' => (int)$body['capacity'],
            ':l' => trim($body['location']),
            ':d' => trim($body['description'] ?? ''),
            ':f' => trim($body['facilities'] ?? ''),
            ':b' => $body['bookable_by'],
            ':s' => $status,
            ':id' => $id,
        ]);
        json_response(['success' => true, 'message' => 'Resource updated successfully.']);

    case 'toggle':
        $id = (int)($body['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT status FROM resources WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cur = $stmt->fetchColumn();
        if ($cur === false) json_response(['error' => 'Resource not found.'], 404);

        $next = match ($cur) {
            'active'   => 'inactive',
            'inactive' => 'active',
            'maintenance' => 'active',
            default    => 'active',
        };
        $upd = $pdo->prepare("UPDATE resources SET status = :s WHERE id = :id");
        $upd->execute([':s' => $next, ':id' => $id]);
        json_response(['success' => true, 'message' => "Resource set to {$next}."]);

    case 'delete':
        $id = (int)($body['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id FROM resources WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) json_response(['error' => 'Resource not found.'], 404);

        $upd = $pdo->prepare("DELETE FROM resources WHERE id = :id");
        $upd->execute([':id' => $id]);
        json_response(['success' => true, 'message' => 'Resource deleted.']);

    default:
        json_response(['error' => 'Unknown action.'], 400);
}
