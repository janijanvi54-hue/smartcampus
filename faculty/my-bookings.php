<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('faculty', '/login.php');
$pdo = db();

$page_title = 'My Bookings & Recommendations';
$active = 'bookings';

$filter = $_GET['filter'] ?? 'upcoming';
$today = date('Y-m-d');

$where = ["b.user_id = :uid"];
$params = [':uid' => (int)$u['id']];

switch ($filter) {
    case 'past':
        $where[] = "b.date < :dt OR b.status IN ('completed')";
        $params[':dt'] = $today;
        break;
    case 'all':
        break;
    default:
        $where[] = "(b.date > :dt OR (b.date = :dt2 AND b.end_time > :now))";
        $params[':dt'] = $today; $params[':dt2'] = $today; $params[':now'] = date('H:i:s');
}

$sql = "SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location, r.capacity AS capacity
        FROM bookings b JOIN resources r ON r.id = b.resource_id
        WHERE " . implode(' AND ', $where) . " ORDER BY b.date DESC, b.start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// recommendations for faculty (all time)
$recs = $pdo->prepare(
    "SELECT rec.*, rr.name AS requested_name, rrc.name AS recommended_name, rrc.capacity AS recommended_capacity,
            b.date AS booking_date, b.start_time AS start_time, b.end_time AS end_time,
            b.expected_users AS expected_users, b.status AS booking_status, b.id AS booking_id
     FROM recommendations rec
     JOIN resources rr ON rr.id = rec.requested_resource_id
     JOIN resources rrc ON rrc.id = rec.recommended_resource_id
     LEFT JOIN bookings b ON b.id = rec.booking_id
     WHERE b.user_id = :uid
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
    <a href="<?= url('/faculty/book-resource.php') ?>" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-calendar-plus me-1"></i>New Booking</a>
</div>

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
                            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>No bookings here yet. <a href="<?= url('/faculty/book-resource.php') ?>">Book a resource</a>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($b['resource_name']) ?></div>
                                <small class="text-muted"><?= e(resource_type_label($b['resource_type'])) ?> &middot; <?= e($b['location']) ?></small>
                            </td>
                            <td><?= fmt_date($b['date']) ?></td>
                            <td><?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></td>
                            <td><?= (int)$b['expected_users'] ?>/<?= (int)$b['capacity'] ?></td>
                            <td><span class="badge text-bg-<?= booking_status_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td class="text-end">
                                <a href="<?= url('/faculty/book-resource.php?resource_id=' . (int)$b['resource_id']) ?>" class="btn btn-sm btn-outline-primary" title="Find alternatives"><i class="bi bi-magic"></i></a>
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
                    <p class="text-muted small mb-0 text-center py-4">No recommendations yet. Use the booking wizard to get smart alternatives when a resource is unavailable or overcrowded.</p>
                <?php else: ?>
                    <?php foreach ($recommendations as $r): ?>
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="rec-badge" style="background:#e8f6ee;color:#166534"><i class="bi bi-magic"></i>Score <?= (float)$r['score'] ?></span>
                            <span class="badge text-bg-light border"><?= ucfirst((string)$r['status']) ?></span>
                        </div>
                        <div class="small">
                            <div><span class="text-muted">Requested:</span> <s><?= e($r['requested_name']) ?></s></div>
                            <div><span class="text-muted">Recommended:</span> <strong class="text-success"><?= e($r['recommended_name']) ?></strong> <small class="text-muted">(cap. <?= (int)$r['recommended_capacity'] ?>)</small></div>
                            <?php if ($r['booking_date']): ?>
                            <div class="text-muted"><?= fmt_date($r['booking_date']) ?> &middot; <?= fmt_time($r['start_time']) ?> - <?= fmt_time($r['end_time']) ?> &middot; <?= (int)$r['expected_users'] ?> students</div>
                            <?php endif; ?>
                            <div class="text-muted mt-1" style="font-size:.78rem"><?= e($r['reason']) ?></div>
                            <?php if ($r['booking_status'] === 'pending'): ?>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-success" onclick="acceptRecommendation(<?= (int)$r['booking_id'] ?>, <?= (int)$r['recommended_resource_id'] ?>)"><i class="bi bi-check-lg me-1"></i>Accept</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="declineRecommendation(<?= (int)$r['id'] ?>)">Decline</button>
                            </div>
                            <?php endif; ?>
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
