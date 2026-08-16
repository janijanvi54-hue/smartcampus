<?php
/**
 * /api/export.php  (admin only)
 * GET ?report=resources|bookings|overcrowding|underutilized|complaints|users
 * Streams a CSV file download.
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_role('admin');
$pdo = db();

$report = $_GET['report'] ?? 'resources';

$columns = [];
$rows = [];

switch ($report) {
    case 'resources':
        $columns = ['ID', 'Name', 'Type', 'Capacity', 'Location', 'Avg Utilization %', 'Avg Users', 'Status'];
        $data = utilization_by_resource($pdo);
        foreach ($data as $r) {
            $rows[] = [$r['id'], $r['name'], resource_type_label($r['type']), $r['capacity'], $r['location'] ?? '', $r['avg_util'], $r['avg_users'], $r['status'] ?? 'active'];
        }
        break;

    case 'bookings':
        $columns = ['ID', 'User', 'Role', 'Resource', 'Type', 'Date', 'Start', 'End', 'Expected Users', 'Purpose', 'Status'];
        $data = $pdo->query(
            "SELECT b.*, u.name AS user_name, u.role AS user_role, r.name AS resource_name, r.type AS resource_type
             FROM bookings b JOIN users u ON u.id = b.user_id JOIN resources r ON r.id = b.resource_id
             ORDER BY b.date DESC"
        )->fetchAll();
        foreach ($data as $b) {
            $rows[] = [$b['id'], $b['user_name'], $b['user_role'], $b['resource_name'], resource_type_label($b['resource_type']),
                       $b['date'], $b['start_time'], $b['end_time'], $b['expected_users'], $b['purpose'], $b['status']];
        }
        break;

    case 'overcrowding':
        $columns = ['Name', 'Type', 'Capacity', 'Avg Utilization %', 'Peak Utilization %', 'Avg Users'];
        foreach (overcrowded_resources($pdo) as $r) {
            $rows[] = [$r['name'], resource_type_label($r['type']), $r['capacity'], $r['avg_util'], $r['peak_util'], $r['avg_users']];
        }
        break;

    case 'underutilized':
        $columns = ['Name', 'Type', 'Capacity', 'Avg Utilization %', 'Avg Users'];
        foreach (under_utilized_resources($pdo) as $r) {
            $rows[] = [$r['name'], resource_type_label($r['type']), $r['capacity'], $r['avg_util'], $r['avg_users']];
        }
        break;

    case 'complaints':
        $columns = ['ID', 'User', 'Resource', 'Category', 'Priority', 'Status', 'Reported', 'Resolved'];
        $data = $pdo->query(
            "SELECT c.*, u.name AS user_name, r.name AS resource_name
             FROM complaints c JOIN users u ON u.id = c.user_id LEFT JOIN resources r ON r.id = c.resource_id
             ORDER BY c.created_at DESC"
        )->fetchAll();
        foreach ($data as $c) {
            $rows[] = [$c['id'], $c['user_name'], $c['resource_name'] ?? '', $c['category'], $c['priority'], $c['status'], $c['created_at'], $c['resolved_at'] ?? ''];
        }
        break;

    case 'users':
        $columns = ['ID', 'Name', 'Email', 'Role', 'Department', 'Identifier', 'Status', 'Total Bookings', 'Created'];
        $data = $pdo->query(
            "SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS total
             FROM users u ORDER BY u.role, u.name"
        )->fetchAll();
        foreach ($data as $u2) {
            $rows[] = [$u2['id'], $u2['name'], $u2['email'], $u2['role'], $u2['department'], $u2['user_identifier'], $u2['status'], $u2['total'], $u2['created_at']];
        }
        break;

    default:
        json_response(['error' => 'Unknown report type.'], 400);
}

// Stream CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="smartcampus_' . $report . '_' . date('Ymd_His') . '.csv"');

$fh = fopen('php://output', 'w');
fputcsv($fh, $columns);
foreach ($rows as $row) {
    fputcsv($fh, $row);
}
fclose($fh);
exit;
