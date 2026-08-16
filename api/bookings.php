<?php
/**
 * /api/bookings.php
 * GET  ?action=list&scope=mine|all&status=&date=  -> list bookings
 * POST { action: 'create', resource_id, date, start, end, expected_users, purpose }
 * POST { action: 'cancel', booking_id }
 * POST { action: 'status', booking_id, status }  (admin only)
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $scope  = $_GET['scope'] ?? 'mine';
    $status = $_GET['status'] ?? '';
    $date   = $_GET['date'] ?? '';

    $sql = "SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS resource_location,
                   r.capacity AS resource_capacity, u.name AS user_name
            FROM bookings b
            JOIN resources r ON r.id = b.resource_id
            JOIN users u ON u.id = b.user_id";
    $where = [];
    $params = [];

    if ($u['role'] === 'admin') {
        if ($scope === 'mine') { $where[] = "b.user_id = :uid"; $params[':uid'] = (int)$u['id']; }
    } elseif ($u['role'] === 'faculty') {
        if ($scope === 'mine') { $where[] = "b.user_id = :uid"; $params[':uid'] = (int)$u['id']; }
        elseif ($scope === 'all') { /* faculty sees all? no - deny */ }
    } else {
        // students always see only their own bookings
        $where[] = "b.user_id = :uid";
        $params[':uid'] = (int)$u['id'];
    }

    if ($status !== '' && in_array($status, BOOKING_STATUSES, true)) {
        $where[] = "b.status = :st";
        $params[':st'] = $status;
    }
    if ($date !== '') {
        $where[] = "b.date = :dt";
        $params[':dt'] = $date;
    }
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY b.date DESC, b.start_time DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    json_response(['success' => true, 'count' => count($rows), 'bookings' => $rows]);
}

if ($method === 'POST') {
    $body = json_input();
    $action = $body['action'] ?? '';

    switch ($action) {

        case 'create':
            $resourceId  = (int)($body['resource_id'] ?? 0);
            $date        = trim($body['date'] ?? '');
            $start       = trim($body['start'] ?? '');
            $end         = trim($body['end'] ?? '');
            $users       = (int)($body['expected_users'] ?? 0);
            $purpose     = trim($body['purpose'] ?? '');

            if ($resourceId <= 0) json_response(['error' => 'Please choose a resource.'], 400);
            if ($purpose === '') json_response(['error' => 'Please provide a purpose for the booking.'], 400);

            $stmt = db()->prepare("SELECT bookable_by FROM resources WHERE id = :id");
            $stmt->execute([':id' => $resourceId]);
            $bookableBy = $stmt->fetchColumn();
            if ($bookableBy === false) json_response(['error' => 'Resource not found.'], 404);
            if (!can_role_book($u['role'], $bookableBy)) {
                json_response(['error' => 'This resource is not available for your role to book.'], 403);
            }

            [$ok, $errors] = validate_booking(db(), $resourceId, $date, $start, $end, $users);
            if (!$ok) json_response(['error' => 'Booking cannot be created.', 'errors' => $errors], 422);

            $pdo = db();
            $bookingId = create_booking($pdo, (int)$u['id'], $resourceId, $date, $start, $end, $users, $purpose);

            $autoApprove = utilization_thresholds()['auto_approve'];
            if ($autoApprove) {
                $upd = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
                $upd->execute([':id' => $bookingId]);
            }

            $stmt = $pdo->prepare("SELECT name FROM resources WHERE id = :id");
            $stmt->execute([':id' => $resourceId]);
            $resName = $stmt->fetchColumn();

            $statusLabel = $autoApprove ? 'approved' : 'pending approval';
            send_notification($pdo, (int)$u['id'], 'Booking submitted',
                "Your booking request for {$resName} on " . fmt_date($date) . " ({$start} - {$end}) has been submitted. Status: {$statusLabel}.", 'booking');

            json_response([
                'success' => true,
                'booking_id' => $bookingId,
                'auto_approved' => $autoApprove,
                'message' => "Booking request created successfully and is now {$statusLabel}.",
            ], 201);
            break;

        case 'recommend_accept':
            $bookingId = (int)($body['booking_id'] ?? 0);
            $resourceId = (int)($body['resource_id'] ?? 0);

            $stmt = db()->prepare("SELECT * FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);
            $orig = $stmt->fetch();
            if (!$orig) json_response(['error' => 'Original booking not found.'], 404);
            if ((int)$orig['user_id'] !== (int)$u['id']) json_response(['error' => 'Not your booking.'], 403);

            $stmt = db()->prepare("SELECT * FROM resources WHERE id = :id");
            $stmt->execute([':id' => $resourceId]);
            $newRes = $stmt->fetch();
            if (!$newRes) json_response(['error' => 'Resource not found.'], 404);
            if (!can_role_book($u['role'], $newRes['bookable_by'] ?? 'all')) {
                json_response(['error' => 'The recommended resource is not available for your role.'], 403);
            }

            [$ok, $errors] = validate_booking(db(), $resourceId, $orig['date'], $orig['start_time'], $orig['end_time'], (int)$orig['expected_users']);
            if (!$ok) json_response(['error' => 'The recommended resource is no longer available.', 'errors' => $errors], 422);

            $pdo = db();
            $newBookingId = create_booking($pdo, (int)$u['id'], $resourceId, $orig['date'], $orig['start_time'], $orig['end_time'], (int)$orig['expected_users'], $orig['purpose']);

            // mark old booking cancelled, update recommendation status
            $upd = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
            $upd->execute([':id' => $bookingId]);
            $upd = $pdo->prepare("UPDATE recommendations SET status = 'accepted' WHERE booking_id = :id");
            $upd->execute([':id' => $bookingId]);

            send_notification($pdo, (int)$u['id'], 'Recommendation accepted',
                "You accepted the recommendation: {$newRes['name']} has been booked for " . fmt_date($orig['date']) . ".",
                'recommendation');

            json_response(['success' => true, 'booking_id' => $newBookingId,
                'message' => "Recommendation accepted. {$newRes['name']} has been booked successfully."]);
            break;

        case 'cancel':
            $bookingId = (int)($body['booking_id'] ?? 0);
            $stmt = db()->prepare("SELECT b.*, r.name AS resource_name FROM bookings b JOIN resources r ON r.id = b.resource_id WHERE b.id = :id");
            $stmt->execute([':id' => $bookingId]);
            $b = $stmt->fetch();
            if (!$b) json_response(['error' => 'Booking not found'], 404);

            // Only the owner may cancel (admins handled via status action)
            if ((int)$b['user_id'] !== (int)$u['id'] && $u['role'] !== 'admin') {
                json_response(['error' => 'You are not allowed to cancel this booking.'], 403);
            }
            if (in_array($b['status'], ['completed', 'cancelled', 'rejected'], true)) {
                json_response(['error' => "This booking cannot be cancelled (status: {$b['status']})."], 422);
            }

            $upd = db()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
            $upd->execute([':id' => $bookingId]);
            send_notification(db(), (int)$b['user_id'], 'Booking cancelled',
                "Your booking for {$b['resource_name']} on " . fmt_date($b['date']) . " was cancelled.", 'booking');
            json_response(['success' => true, 'message' => 'Booking cancelled successfully.']);
            break;

        case 'status':
            api_require_role('admin');
            $bookingId = (int)($body['booking_id'] ?? 0);
            $newStatus = $body['status'] ?? '';
            if (!in_array($newStatus, ['approved', 'rejected', 'cancelled', 'pending', 'completed'], true)) {
                json_response(['error' => 'Invalid status.'], 400);
            }
            $stmt = db()->prepare("SELECT b.*, r.name AS resource_name FROM bookings b JOIN resources r ON r.id = b.resource_id WHERE b.id = :id");
            $stmt->execute([':id' => $bookingId]);
            $b = $stmt->fetch();
            if (!$b) json_response(['error' => 'Booking not found'], 404);

            // Prevent double-approval conflicts: approving must still respect slot conflicts
            if ($newStatus === 'approved' && $b['status'] !== 'approved') {
                if (!is_resource_available(db(), (int)$b['resource_id'], $b['date'], $b['start_time'], $b['end_time'], (int)$b['id'])) {
                    json_response(['error' => 'Cannot approve: this time slot now conflicts with another booking.'], 422);
                }
            }

            $upd = db()->prepare("UPDATE bookings SET status = :s WHERE id = :id");
            $upd->execute([':s' => $newStatus, ':id' => $bookingId]);

            $labels = ['approved' => 'approved', 'rejected' => 'rejected', 'cancelled' => 'cancelled', 'pending' => 'pending again', 'completed' => 'marked completed'];
            send_notification(db(), (int)$b['user_id'], 'Booking ' . $labels[$newStatus],
                "Your booking for {$b['resource_name']} on " . fmt_date($b['date']) . " has been {$labels[$newStatus]} by the administration.", 'booking');

            json_response(['success' => true, 'message' => "Booking marked as {$newStatus}."]);
            break;

        default:
            json_response(['error' => 'Unknown action.'], 400);
    }
}

json_response(['error' => 'Method not allowed'], 405);
