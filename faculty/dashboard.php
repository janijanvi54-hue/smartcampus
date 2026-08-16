<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('faculty', '/login.php');
$pdo = db();

$tab = $_GET['tab'] ?? 'overview';
$page_title = 'Faculty Dashboard';
$active = 'dashboard';

$today = date('Y-m-d');

// stats
$availableRooms = 0;
$availableLabs = 0;
foreach (all_resources($pdo) as $r) {
    if ($r['type'] === 'classroom' && $r['avg_utilization'] <= 70) $availableRooms++;
    if ($r['type'] === 'computer_lab' && $r['avg_utilization'] <= 70) $availableLabs++;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND status IN ('pending','approved') AND date >= CURDATE()");
$stmt->execute([':uid' => (int)$u['id']]);
$activeBookings = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND status = 'pending'");
$stmt->execute([':uid' => (int)$u['id']]);
$pendingRequests = (int)$stmt->fetchColumn();

// today's bookings
$stmt = $pdo->prepare("SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location
                       FROM bookings b JOIN resources r ON r.id = b.resource_id
                       WHERE b.user_id = :uid AND b.date = :dt ORDER BY b.start_time");
$stmt->execute([':uid' => (int)$u['id'], ':dt' => $today]);
$todaysBookings = $stmt->fetchAll();

// upcoming
$stmt = $pdo->prepare("SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location
                       FROM bookings b JOIN resources r ON r.id = b.resource_id
                       WHERE b.user_id = :uid AND b.status IN ('pending','approved') AND b.date > :dt
                       ORDER BY b.date, b.start_time LIMIT 6");
$stmt->execute([':uid' => (int)$u['id'], ':dt' => $today]);
$upcomingBookings = $stmt->fetchAll();

// pending requests
$stmt = $pdo->prepare("SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location
                       FROM bookings b JOIN resources r ON r.id = b.resource_id
                       WHERE b.user_id = :uid AND b.status = 'pending' ORDER BY b.date, b.start_time");
$stmt->execute([':uid' => (int)$u['id']]);
$pendingList = $stmt->fetchAll();

// recommendations
$recs = $pdo->prepare(
    "SELECT rec.*, rr.name AS requested_name, rrc.name AS recommended_name, rrc.capacity AS recommended_capacity,
            b.date AS booking_date, b.start_time AS start_time, b.end_time AS end_time,
            b.expected_users AS expected_users, b.status AS booking_status, b.id AS booking_id
     FROM recommendations rec
     JOIN resources rr ON rr.id = rec.requested_resource_id
     JOIN resources rrc ON rrc.id = rec.recommended_resource_id
     LEFT JOIN bookings b ON b.id = rec.booking_id
     WHERE b.user_id = :uid
     ORDER BY rec.created_at DESC LIMIT 5"
);
$recs->execute([':uid' => (int)$u['id']]);
$recommendations = $recs->fetchAll();

// announcements + notifications
$announcements = $pdo->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC, id DESC LIMIT 4")->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT 8");
$stmt->execute([':uid' => (int)$u['id']]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<ul class="nav sc-tabs gap-2 mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="?tab=overview"><i class="bi bi-speedometer2 me-1"></i>Overview</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'notifications' ? 'active' : '' ?>" href="?tab=notifications"><i class="bi bi-bell me-1"></i>Notifications</a></li>
</ul>

<?php if ($tab === 'overview'): ?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="sc-card p-4 sc-banner-navy-2">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-1">Hello, <?= e(explode(' ', $u['name'])[0]) ?>!</h2>
                    <p class="mb-0 opacity-75">Plan your classes and events with live availability and smart alternatives.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="<?= url('/faculty/book-resource.php') ?>" class="btn btn-gold px-4"><i class="bi bi-calendar-plus me-2"></i>Book a Resource</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#2563eb"><i class="bi bi-easel"></i></div>
            <div class="sc-stat-value"><?= (int)$availableRooms ?></div>
            <div class="sc-stat-label">Available Classrooms</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#7c3aed"><i class="bi bi-display"></i></div>
            <div class="sc-stat-value"><?= (int)$availableLabs ?></div>
            <div class="sc-stat-label">Available Labs</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#16a34a"><i class="bi bi-calendar-check"></i></div>
            <div class="sc-stat-value"><?= (int)$activeBookings ?></div>
            <div class="sc-stat-label">Active Bookings</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#f59e0b"><i class="bi bi-hourglass-split"></i></div>
            <div class="sc-stat-value"><?= (int)$pendingRequests ?></div>
            <div class="sc-stat-label">Pending Requests</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="sc-card mb-4">
            <div class="card-header-sc">
                <i class="bi bi-calendar3 text-primary"></i> Today's Classes &amp; Events
                <a href="<?= url('/faculty/my-bookings.php') ?>" class="ms-auto small fw-normal">Manage all</a>
            </div>
            <div class="p-3">
                <?php if (!$todaysBookings): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Nothing scheduled today.
                    </div>
                <?php else: ?>
                    <ul class="sc-timeline mb-0">
                        <?php foreach ($todaysBookings as $b): ?>
                        <li>
                            <span class="tl-dot" style="border-color:<?= $b['status'] === 'approved' ? '#16a34a' : '#f59e0b' ?>"></span>
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <strong><?= e($b['resource_name']) ?></strong>
                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($b['location']) ?> &middot; <?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?> &middot; <?= (int)$b['expected_users'] ?> users</div>
                                </div>
                                <span class="badge text-bg-<?= booking_status_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="sc-card mb-4">
            <div class="card-header-sc">
                <i class="bi bi-hourglass-split text-warning"></i> Pending Booking Requests
                <a href="<?= url('/faculty/my-bookings.php') ?>" class="ms-auto small fw-normal">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table sc-table mb-0">
                    <thead><tr><th>Resource</th><th>Date</th><th>Time</th><th>Users</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$pendingList): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No pending requests.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingList as $b): ?>
                        <tr>
                            <td><div class="fw-semibold"><?= e($b['resource_name']) ?></div><small class="text-muted"><?= e($b['location']) ?></small></td>
                            <td><?= fmt_date($b['date']) ?></td>
                            <td><?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></td>
                            <td><?= (int)$b['expected_users'] ?></td>
                            <td class="text-end">
                                <a href="<?= url('/faculty/book-resource.php?resource_id=' . (int)$b['resource_id']) ?>" class="btn btn-sm btn-outline-primary">Alternatives</a>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelBooking(<?= (int)$b['id'] ?>)"><i class="bi bi-x-circle"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-calendar-event text-success"></i> Upcoming Bookings</div>
            <div class="table-responsive">
                <table class="table sc-table mb-0">
                    <thead><tr><th>Resource</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$upcomingBookings): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No upcoming bookings.</td></tr>
                    <?php else: ?>
                        <?php foreach ($upcomingBookings as $b): ?>
                        <tr>
                            <td><div class="fw-semibold"><?= e($b['resource_name']) ?></div><small class="text-muted"><?= e($b['location']) ?></small></td>
                            <td><?= fmt_date($b['date']) ?></td>
                            <td><?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></td>
                            <td><span class="badge text-bg-<?= booking_status_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('/faculty/my-bookings.php') ?>">Manage</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="sc-card mb-4">
            <div class="card-header-sc"><i class="bi bi-magic text-success"></i> Smart Recommendations</div>
            <div class="p-3">
                <?php if (!$recommendations): ?>
                    <p class="text-muted small mb-0 text-center py-4">Recommendations appear here when a requested resource is unavailable or overcrowded.</p>
                <?php else: ?>
                    <?php foreach ($recommendations as $r): ?>
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="rec-badge" style="background:#e8f6ee;color:#166534"><i class="bi bi-magic"></i>Recommended</span>
                            <small class="text-muted">Score <?= (float)$r['score'] ?></small>
                        </div>
                        <div class="small">
                            <div><span class="text-muted">Requested:</span> <s><?= e($r['requested_name']) ?></s></div>
                            <div><span class="text-muted">Try:</span> <strong class="text-success"><?= e($r['recommended_name']) ?></strong> <small class="text-muted">(cap. <?= (int)$r['recommended_capacity'] ?>)</small></div>
                            <?php if ($r['booking_date']): ?>
                                <div class="text-muted"><?= fmt_date($r['booking_date']) ?> &middot; <?= fmt_time($r['start_time']) ?> - <?= fmt_time($r['end_time']) ?></div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <?php if ($r['booking_status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success" onclick="acceptRecommendation(<?= (int)$r['booking_id'] ?>, <?= (int)$r['recommended_resource_id'] ?>)"><i class="bi bi-check-lg me-1"></i>Accept</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="declineRecommendation(<?= (int)$r['id'] ?>)">Decline</button>
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

        <div class="sc-card mb-4">
            <div class="card-header-sc"><i class="bi bi-megaphone text-warning"></i> Announcements</div>
            <div class="p-3">
                <?php foreach ($announcements as $a): ?>
                    <div class="insight-card mb-2">
                        <div class="fw-semibold small"><?= e($a['title']) ?></div>
                        <div class="text-muted small"><?= e(mb_strimwidth($a['message'], 0, 100, '...')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-bell text-danger"></i> Recent Notifications</div>
            <div class="p-3">
                <?php foreach ($notifications as $n): ?>
                    <div class="d-flex gap-2 mb-3 <?= $n['is_read'] ? 'opacity-50' : '' ?>">
                        <i class="bi bi-bell text-<?= $n['is_read'] ? 'muted' : 'primary' ?>"></i>
                        <div class="small">
                            <div class="fw-semibold"><?= e($n['title']) ?></div>
                            <div class="text-muted"><?= e(mb_strimwidth($n['message'], 0, 80, '...')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php else: /* notifications tab */ ?>

<div class="sc-card">
    <div class="card-header-sc">
        <i class="bi bi-bell text-danger"></i> Notifications
        <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="markAllRead()"><i class="bi bi-check2-all me-1"></i>Mark all read</button>
    </div>
    <div class="p-3">
        <?php if (!$notifications): ?>
            <div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-2"></i>No notifications.</div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                <div class="list-group-item d-flex gap-3 py-3 <?= $n['is_read'] ? '' : 'bg-light' ?>">
                    <i class="bi bi-bell fs-4 text-primary"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <strong><?= e($n['title']) ?></strong>
                            <small class="text-muted"><?= date('d M, g:i A', strtotime($n['created_at'])) ?></small>
                        </div>
                        <p class="text-muted small mb-1"><?= e($n['message']) ?></p>
                        <button class="btn btn-sm btn-link p-0" onclick="markRead(<?= (int)$n['id'] ?>)"><?= $n['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php
$extra_scripts = '<script src="' . url('/assets/js/booking.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
