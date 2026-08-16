<?php
/**
 * GET /api/resources.php
 * List + search + filter active resources with live utilization.
 *
 * Query params: type, search, date, start, end, capacity, location,
 *               status (availability filter), limit
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();

$type     = $_GET['type'] ?? '';
$search   = trim($_GET['search'] ?? '');
$capacity = (int)($_GET['capacity'] ?? 0);
$location = trim($_GET['location'] ?? '');
$avail    = $_GET['status'] ?? '';          // available | crowded | under | high
$date     = $_GET['date'] ?? date('Y-m-d');
$start    = $_GET['start'] ?? '08:00';
$end      = $_GET['end'] ?? '21:00';
$limit    = (int)($_GET['limit'] ?? 100);

$sql = "SELECT * FROM resources WHERE status = 'active'";
$params = [];
if ($u['role'] !== 'admin') {
    $sql .= " AND bookable_by IN ('all', :role)";
    $params[':role'] = $u['role'];
}
if ($type && in_array($type, array_keys(RESOURCE_TYPES), true)) {
    $sql .= " AND type = :type";
    $params[':type'] = $type;
}
if ($search !== '') {
    $sql .= " AND (name LIKE :s OR location LIKE :s2 OR description LIKE :s3 OR facilities LIKE :s4)";
    $like = "%{$search}%";
    $params[':s'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
}
if ($capacity > 0) {
    $sql .= " AND capacity >= :cap";
    $params[':cap'] = $capacity;
}
if ($location !== '') {
    $sql .= " AND location LIKE :loc";
    $params[':loc'] = "%{$location}%";
}
$sql .= " ORDER BY type, name LIMIT " . min(500, max(1, $limit));
$stmt = db()->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$result = [];
foreach ($resources as $res) {
    $util = resource_utilization(db(), (int)$res['id'], 30);
    $cls = classify_utilization($util['avg_utilization']);
    $free = is_resource_available(db(), (int)$res['id'], $date, $start, $end);

    // availability filter
    if ($avail === 'available' && !$free) continue;
    if ($avail === 'booked' && $free) continue;
    if ($avail === 'crowded' && $cls['key'] !== 'overcrowded') continue;
    if ($avail === 'under' && $cls['key'] !== 'under') continue;
    if ($avail === 'high' && $cls['key'] !== 'high') continue;

    $result[] = [
        'id'              => (int)$res['id'],
        'name'            => $res['name'],
        'type'            => $res['type'],
        'type_label'      => resource_type_label($res['type']),
        'capacity'        => (int)$res['capacity'],
        'location'        => $res['location'],
        'description'     => $res['description'],
        'facilities'      => $res['facilities'],
        'bookable_by'     => $res['bookable_by'] ?? 'all',
        'status'          => $res['status'],
        'avg_utilization' => $util['avg_utilization'],
        'avg_users'       => $util['avg_users'],
        'util_class'      => $cls['key'],
        'util_label'      => $cls['label'],
        'available'       => $free,
    ];
}

json_response(['success' => true, 'count' => count($result), 'resources' => $result]);
