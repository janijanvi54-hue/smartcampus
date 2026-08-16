<?php
/**
 * POST /api/availability.php
 * Body: { resource_id, date, start, end, expected_users }
 * Returns whether the slot is free and fits capacity, plus expected utilization.
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
$body = json_input();

$resourceId = (int)($body['resource_id'] ?? 0);
$date  = trim($body['date'] ?? '');
$start = trim($body['start'] ?? '');
$end   = trim($body['end'] ?? '');
$users = (int)($body['expected_users'] ?? 0);

if ($resourceId <= 0) json_response(['error' => 'resource_id is required'], 400);

$stmt = db()->prepare("SELECT * FROM resources WHERE id = :id");
$stmt->execute([':id' => $resourceId]);
$res = $stmt->fetch();
if (!$res) json_response(['error' => 'Resource not found'], 404);

[$ok, $errors] = validate_booking(db(), $resourceId, $date, $start, $end, $users);

$util = resource_utilization(db(), $resourceId, 30);
$expectedUtil = $users > 0 ? expected_utilization($users, (int)$res['capacity']) : 0;

json_response([
    'success' => true,
    'available' => $ok,
    'errors'  => $errors,
    'capacity' => (int)$res['capacity'],
    'expected_utilization' => $expectedUtil,
    'current_utilization'  => $util['avg_utilization'],
    'util_class' => classify_utilization($expectedUtil)['key'],
    'message' => $ok
        ? 'This resource is available for the requested slot.'
        : ($errors[0] ?? 'This slot cannot be booked.'),
]);
