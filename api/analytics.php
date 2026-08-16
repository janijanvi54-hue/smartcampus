<?php
/**
 * /api/analytics.php  (admin only)
 * GET ?chart=type|daily|hourly|classroom|lab|library|status|overview
 * Returns JSON payloads for Chart.js.
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_role('admin');
$pdo = db();
$chart = $_GET['chart'] ?? 'overview';

switch ($chart) {

    case 'overview':
        $totalRes  = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
        $activeRes = (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE status = 'active'")->fetchColumn();
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $todayBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE date = CURDATE()")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
        $complaintsOpen = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status != 'resolved'")->fetchColumn();
        $under = count(under_utilized_resources($pdo));
        $over  = count(overcrowded_resources($pdo));
        json_response([
            'success' => true,
            'data' => [
                'total_resources' => $totalRes,
                'active_resources' => $activeRes,
                'occupied' => $activeRes - $under - $over,
                'total_users' => $totalUsers,
                'today_bookings' => $todayBookings,
                'pending_requests' => $pending,
                'under_utilized' => $under,
                'overcrowded' => $over,
                'open_complaints' => $complaintsOpen,
            ],
        ]);

    case 'type':
        $rows = utilization_by_type($pdo);
        json_response([
            'success' => true,
            'labels' => array_map(fn($r) => resource_type_label($r['type']), $rows),
            'values' => array_map(fn($r) => (float)$r['avg_util'], $rows),
        ]);

    case 'daily':
        $rows = daily_booking_trend($pdo, 14);
        json_response([
            'success' => true,
            'labels' => array_map(fn($r) => date('d M', strtotime($r['date'])), $rows),
            'total' => array_map(fn($r) => $r['total'], $rows),
            'approved' => array_map(fn($r) => $r['approved'], $rows),
            'pending' => array_map(fn($r) => $r['pending'], $rows),
        ]);

    case 'hourly':
        $rows = hourly_occupancy($pdo);
        json_response([
            'success' => true,
            'labels' => array_map(fn($r) => $r['hour'], $rows),
            'users' => array_map(fn($r) => $r['avg_users'], $rows),
            'util'  => array_map(fn($r) => $r['avg_util'], $rows),
        ]);

    case 'resource':
        $type = $_GET['type'] ?? null;
        $rows = utilization_by_resource($pdo, $type);
        json_response([
            'success' => true,
            'title' => $type ? resource_type_label($type) . ' utilization' : 'Resource utilization',
            'labels' => array_map(fn($r) => $r['name'], $rows),
            'values' => array_map(fn($r) => (float)$r['avg_util'], $rows),
            'capacities' => array_map(fn($r) => (int)$r['capacity'], $rows),
        ]);

    case 'status':
        $rows = booking_status_distribution($pdo);
        json_response([
            'success' => true,
            'labels' => array_map(fn($r) => ucfirst($r['status']), $rows),
            'values' => array_map(fn($r) => (int)$r['total'], $rows),
        ]);

    default:
        json_response(['error' => 'Unknown chart type.'], 400);
}
