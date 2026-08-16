<?php
/**
 * GET /api/resource.php?id=..&date=..&start=..&end=..
 * Detailed resource info + utilization + availability of time slots.
 */
require_once __DIR__ . '/bootstrap.php';

api_require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_response(['error' => 'Resource id is required'], 400);

$stmt = db()->prepare("SELECT * FROM resources WHERE id = :id");
$stmt->execute([':id' => $id]);
$res = $stmt->fetch();
if (!$res) json_response(['error' => 'Resource not found'], 404);

$date  = $_GET['date'] ?? date('Y-m-d');
$start = $_GET['start'] ?? '08:00';
$end   = $_GET['end'] ?? '21:00';

$util = resource_utilization(db(), $id, 30);
$cls  = classify_utilization($util['avg_utilization']);
$free = is_resource_available(db(), $id, $date, $start, $end);

// Build hourly time slots for the requested date
$slots = [];
$fromHour = 8;
$toHour = 21;
for ($h = $fromHour; $h < $toHour; $h++) {
    $s = sprintf('%02d:00', $h);
    $en = sprintf('%02d:00', $h + 1);
    $slots[] = [
        'start'      => $s,
        'end'        => $en,
        'label'      => date('g:i A', strtotime($s)) . ' - ' . date('g:i A', strtotime($en)),
        'available'  => is_resource_available(db(), $id, $date, $s, $en),
    ];
}

json_response([
    'success' => true,
    'resource' => [
        'id'            => (int)$res['id'],
        'name'          => $res['name'],
        'type'          => $res['type'],
        'type_label'    => resource_type_label($res['type']),
        'capacity'      => (int)$res['capacity'],
        'location'      => $res['location'],
        'description'   => $res['description'],
        'facilities'    => $res['facilities'],
        'status'        => $res['status'],
        'avg_utilization' => $util['avg_utilization'],
        'avg_users'     => $util['avg_users'],
        'util_class'    => $cls['key'],
        'util_label'    => $cls['label'],
        'available'     => $free,
        'slots'         => $slots,
    ],
]);
