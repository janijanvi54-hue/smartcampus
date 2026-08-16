<?php
/**
 * POST /api/recommend.php
 * Body: { requested_resource_id?, type, date, start, end, expected_users, location? }
 * Runs the Smart Recommendation Engine.
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
$body = json_input();

// Action: decline a stored recommendation
if (!empty($body['action'])) {
    if ($body['action'] === 'decline') {
        $id = (int)($body['id'] ?? 0);
        $stmt = db()->prepare("UPDATE recommendations SET status = 'declined' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        json_response(['success' => true, 'message' => 'Recommendation declined.']);
    }
    json_response(['error' => 'Unknown action.'], 400);
}

$request = [
    'requested_resource_id' => (int)($body['requested_resource_id'] ?? 0),
    'type'        => $body['type'] ?? null,
    'date'        => trim($body['date'] ?? ''),
    'start'       => trim($body['start'] ?? ''),
    'end'         => trim($body['end'] ?? ''),
    'expected_users' => (int)($body['expected_users'] ?? 0),
    'location'    => trim($body['location'] ?? ''),
    'max_results' => 5,
];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $request['date'])) {
    json_response(['error' => 'A valid booking date is required.'], 400);
}
if ($request['expected_users'] <= 0) {
    json_response(['error' => 'Expected users must be at least 1.'], 400);
}
if ($request['start'] >= $request['end']) {
    json_response(['error' => 'End time must be after start time.'], 400);
}

// Also evaluate the originally requested resource for comparison.
$requestedInfo = null;
if ($request['requested_resource_id'] > 0) {
    $stmt = db()->prepare("SELECT * FROM resources WHERE id = :id");
    $stmt->execute([':id' => $request['requested_resource_id']]);
    $reqRes = $stmt->fetch();
    if ($reqRes) {
        [$ok, $errs] = validate_booking(db(), (int)$reqRes['id'], $request['date'], $request['start'], $request['end'], $request['expected_users']);
        $util = resource_utilization(db(), (int)$reqRes['id'], 30);
        $requestedInfo = [
            'id'              => (int)$reqRes['id'],
            'name'            => $reqRes['name'],
            'capacity'        => (int)$reqRes['capacity'],
            'available'       => $ok,
            'expected_utilization' => expected_utilization($request['expected_users'], (int)$reqRes['capacity']),
            'current_utilization'  => $util['avg_utilization'],
            'reasons'         => $errs,
        ];
    }
}

$results = recommend_resources(db(), $request + ['role' => $u['role']]);

json_response([
    'success' => true,
    'requested' => $requestedInfo,
    'results' => array_map(function ($r) {
        return [
            'resource' => [
                'id'       => (int)$r['resource']['id'],
                'name'     => $r['resource']['name'],
                'type'     => $r['resource']['type'],
                'type_label' => resource_type_label($r['resource']['type']),
                'capacity' => (int)$r['resource']['capacity'],
                'location' => $r['resource']['location'],
                'facilities' => $r['resource']['facilities'],
            ],
            'current_utilization'  => $r['avg_utilization'],
            'expected_utilization' => $r['expected_utilization'],
            'score'      => $r['score'],
            'reasons'    => $r['reasons'],
        ];
    }, $results),
]);
