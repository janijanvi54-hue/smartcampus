<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_any_role(['student', 'faculty']);
$pdo = db();

$page_title = 'My Bookings';
$active = 'bookings';

$filter = $_GET['filter'] ?? 'upcoming';
$statusFilter = $_GET['status'] ?? '';
$viewBookingId = (int)($_GET['b'] ?? 0);

$today = date('Y-m-d');
$where = ["b.user_id = :uid"];
$params = [':uid' => (int)$u['id']];

switch ($filter) {
    case 'past':
        $where[] = "b.date < :dt OR (b.date = :dt2 AND b.end_time < :now) OR b.status IN ('completed')";
        $params[':dt'] = $today; $params[':dt2'] = $today; $params[':now'] = date('H:i:s');
        break;
    case 'all':
        break;
    default:
        $where[] = "(b.date > :dt OR (b.date = :dt2 AND b.end_time > :now))";
        $params[':dt'] = $today; $params[':dt2'] = $today; $params[':now'] = date('H:i:s');
}

if ($statusFilter !== '' && in_array($statusFilter, BOOKING_STATUSES, true)) {
    $where[] = "b.status = :st";
    $params[':st'] = $statusFilter;
}

$sql = "SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location, r.capacity AS capacity
        FROM bookings b JOIN resources r ON r.id = b.resource_id
        WHERE " . implode(' AND ', $where) . " ORDER BY b.date DESC, b.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$viewBooking = null;
if ($viewBookingId) {
    foreach ($bookings as $b) if ((int)$b['id'] === $viewBookingId) { $viewBooking = $b; break; }
}

// recommendations for the user
$recs = $pdo->prepare(
    "SELECT rec.*, rr.name AS requested_name, rrc.name AS recommended_name, rrc.capacity AS recommended_capacity,
            b.date AS booking_date, b.start_time AS start_time, b.end_time AS end_time, b.expected_users AS expected_users, b.status AS booking_status
     FROM recommendations rec
     JOIN resources rr ON rr.id = rec.requested_resource_id
     JOIN resources rrc ON rrc.id = rec.recommended_resource_id
     LEFT JOIN bookings b ON b.id = rec.booking_id
     WHERE b.user_id = :uid OR rec.booking_id IS NULL
     ORDER BY rec.created_at DESC"
);
$recs->execute([':uid' => (int)$u['id']]);
$recommendations = $recs->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <div class="btn-group btn-group-sm" role="group">
        <a class="btn btn-outline-primary <?= $filter === 'upcoming' ? 'active' : '' ?>" href="?filter=upcoming">Upcoming</a>
        <a class="btn btn-outline-primary <?= $filter === 'past' ? 'active' : '' ?>" href="?filter=past">Previous</a>
        <a class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">All</a>
    </div>
    <select class="form-select form-select-sm ms-auto" style="width:auto" id="statusFilter" onchange="location.href='?filter=<?= e($filter) ?>&status='+this.value">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'rejected', 'cancelled', 'completed'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($viewBooking): ?>
<div class="modal fade show d-block" tabindex="-1" id="viewModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking #<?= (int)$viewBooking['id'] ?> - <?= e($viewBooking['resource_name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="location.href='?filter=<?= e($filter) ?>'"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 small">
                    <div class="col-6"><span class="text-muted">Date:</span> <strong><?= fmt_date($viewBooking['date']) ?></strong></div>
                    <div class="col-6"><span class="text-muted">Time:</span> <strong><?= fmt_time($viewBooking['start_time']) ?> - <?= fmt_time($viewBooking['end_time']) ?></strong></div>
                    <div class="col-6"><span class="text-muted">Expected users:</span> <strong><?= (int)$viewBooking['expected_users'] ?></strong></div>
                    <div class="col-6"><span class="text-muted">Capacity:</span> <strong><?= (int)$viewBooking['capacity'] ?></strong></div>
                    <div class="col-6"><span class="text-muted">Type:</span> <strong><?= e(resource_type_label($viewBooking['resource_type'])) ?></strong></div>
                    <div class="col-6"><span class="text-muted">Location:</span> <strong><?= e($viewBooking['location']) ?></strong></div>
                    <div class="col-12"><span class="text-muted">Purpose:</span> <strong><?= e($viewBooking['purpose'] ?: '-') ?></strong></div>
                    <div class="col-12"><span class="text-muted">Status:</span> <span class="badge text-bg-<?= booking_status_class($viewBooking['status']) ?>"><?= ucfirst($viewBooking['status']) ?></span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" onclick="location.href='?filter=<?= e($filter) ?>'">Close</button>
                <?php if (in_array($viewBooking['status'], ['pending', 'approved'], true)): ?>
                    <button class="btn btn-danger" onclick="cancelBooking(<?= (int)$viewBooking['id'] ?>)"><i class="bi bi-x-circle me-1"></i>Cancel Booking</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-calendar-check text-primary"></i> <?= ucfirst($filter) ?> bookings</div>
            <div class="table-responsive">
                <table class="table sc-table mb-0">
                    <thead><tr><th>Resource</th><th>Date</th><th>Time</th><th>Users</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$bookings): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                            No bookings here yet. <a href="<?= url('/student/resources.php') ?>">Browse resources</a>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($b['resource_name']) ?></div>
                                <small class="text-muted"><?= e(resource_type_label($b['resource_type'])) ?></small>
                            </td>
                            <td><?= fmt_date($b['date']) ?></td>
                            <td><?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></td>
                            <td><?= (int)$b['expected_users'] ?>/<?= (int)$b['capacity'] ?></td>
                            <td><span class="badge text-bg-<?= booking_status_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td class="text-end">
                                <a href="?filter=<?= e($filter) ?>&b=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <?php if (in_array($b['status'], ['pending', 'approved'], true)): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="cancelBooking(<?= (int)$b['id'] ?>)"><i class="bi bi-x-circle"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-magic text-success"></i> Smart Recommendations</div>
            <div class="p-3">
                <?php if (!$recommendations): ?>
                    <p class="text-muted small mb-0 text-center py-4">No recommendations yet. They appear when the system finds a better alternative for you.</p>
                <?php else: ?>
                    <?php foreach ($recommendations as $r): ?>
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge text-bg-success"><i class="bi bi-magic me-1"></i>Recommended</span>
                            <small class="text-muted">Score <?= (float)$r['score'] ?></small>
                        </div>
                        <div class="small">
                            <div><span class="text-muted">Requested:</span> <strong><?= e($r['requested_name']) ?></strong></div>
                            <div><span class="text-muted">Recommended:</span> <strong class="text-success"><?= e($r['recommended_name']) ?></strong></div>
                            <?php if ($r['booking_date']): ?>
                            <div class="text-muted"><?= fmt_date($r['booking_date']) ?> &middot; <?= fmt_time($r['start_time']) ?> - <?= fmt_time($r['end_time']) ?> &middot; <?= (int)$r['expected_users'] ?> users</div>
                            <?php endif; ?>
                            <div class="text-muted mt-1" style="font-size:.78rem"><?= e(mb_strimwidth((string)$r['reason'], 0, 140, '...')) ?></div>
                            <div class="mt-2">
                                <?php if ($r['booking_status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success" onclick="acceptRecommendation(<?= (int)$r['booking_id'] ?>, <?= (int)$r['recommended_resource_id'] ?>)"><i class="bi bi-check-lg me-1"></i>Accept</button>
                                <?php else: ?>
                                    <span class="badge text-bg-<?= booking_status_class((string)$r['booking_status']) ?>">Booking: <?= ucfirst((string)$r['booking_status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/booking.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
