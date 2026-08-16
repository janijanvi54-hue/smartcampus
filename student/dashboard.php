<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('student', '/login.php');
$pdo = db();

$page_title = 'Student Dashboard';
$active = 'dashboard';

$today = date('Y-m-d');
$now = date('H:i');

// Stat cards
$availableRooms   = 0;
$availableLabs    = 0;
$libraryOccupancy = 0;
$studySpaces      = 0;
$resources = all_resources($pdo);
$avgCounts = array_fill_keys(array_keys(RESOURCE_TYPES), 0);
foreach ($resources as $r) {
    $avgCounts[$r['type']]++;
    if ($r['type'] === 'classroom' && $r['avg_utilization'] <= 70) $availableRooms++;
    if ($r['type'] === 'computer_lab' && $r['avg_utilization'] <= 70) $availableLabs++;
    if ($r['type'] === 'library') $libraryOccupancy = (int)$r['avg_utilization'];
    if ($r['type'] === 'study_room' && $r['avg_utilization'] <= 70) $studySpaces++;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND status = 'approved' AND date >= CURDATE()");
$stmt->execute([':uid' => (int)$u['id']]);
$myBookingsCount = (int)$stmt->fetchColumn();

// Today's bookings
$stmt = $pdo->prepare("SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location
                       FROM bookings b JOIN resources r ON r.id = b.resource_id
                       WHERE b.user_id = :uid AND b.date = :dt ORDER BY b.start_time");
$stmt->execute([':uid' => (int)$u['id'], ':dt' => $today]);
$todaysBookings = $stmt->fetchAll();

// Upcoming booking
$stmt = $pdo->prepare("SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location
                       FROM bookings b JOIN resources r ON r.id = b.resource_id
                       WHERE b.user_id = :uid AND b.status = 'approved' AND b.date > :dt
                       ORDER BY b.date, b.start_time LIMIT 3");
$stmt->execute([':uid' => (int)$u['id'], ':dt' => $today]);
$upcomingBookings = $stmt->fetchAll();

// Notifications & announcements
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT 6");
$stmt->execute([':uid' => (int)$u['id']]);
$notifications = $stmt->fetchAll();

$announcements = $pdo->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC, id DESC LIMIT 4")->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="sc-card p-4 sc-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-1">Welcome back, <span style="color:var(--sc-gold)"><?= e($u['name']) ?></span>!</h2>
                    <p class="mb-0 opacity-75">Ready to find your next study space or lab? Check live availability below.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="<?= url('/student/resources.php') ?>" class="btn btn-gold px-4"><i class="bi bi-search me-2"></i>Find a Resource</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#2563eb"><i class="bi bi-easel"></i></div>
            <div class="sc-stat-value"><?= (int)$availableRooms ?></div>
            <div class="sc-stat-label">Available Classrooms</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#7c3aed"><i class="bi bi-display"></i></div>
            <div class="sc-stat-value"><?= (int)$availableLabs ?></div>
            <div class="sc-stat-label">Available Labs</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#f59e0b"><i class="bi bi-journal-bookmark"></i></div>
            <div class="sc-stat-value"><?= (int)$libraryOccupancy ?>%</div>
            <div class="sc-stat-label">Library Occupancy</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#0ea5e9"><i class="bi bi-book"></i></div>
            <div class="sc-stat-value"><?= (int)$studySpaces ?></div>
            <div class="sc-stat-label">Free Study Spaces</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#16a34a"><i class="bi bi-calendar-check"></i></div>
            <div class="sc-stat-value"><?= (int)$myBookingsCount ?></div>
            <div class="sc-stat-label">My Bookings</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#dc2626"><i class="bi bi-bell"></i></div>
            <div class="sc-stat-value"><?= unread_notifications($pdo, (int)$u['id']) ?></div>
            <div class="sc-stat-label">Notifications</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="sc-card mb-4">
            <div class="card-header-sc">
                <i class="bi bi-calendar3 text-primary"></i> Today's Bookings
                <a href="<?= url('/student/my-bookings.php') ?>" class="ms-auto small fw-normal">View all</a>
            </div>
            <div class="p-3">
                <?php if (!$todaysBookings): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        No bookings today. <a href="<?= url('/student/resources.php') ?>">Find a resource</a> to get started.
                    </div>
                <?php else: ?>
                    <ul class="sc-timeline mb-0">
                        <?php foreach ($todaysBookings as $b): ?>
                        <li>
                            <span class="tl-dot" style="border-color:<?= $b['status'] === 'approved' ? '#16a34a' : '#f59e0b' ?>"></span>
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <strong><?= e($b['resource_name']) ?></strong>
                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($b['location']) ?> &middot; <?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></div>
                                </div>
                                <span class="badge text-bg-<?= booking_status_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="sc-card">
            <div class="card-header-sc">
                <i class="bi bi-calendar-event text-success"></i> Upcoming Bookings
                <a href="<?= url('/student/my-bookings.php') ?>" class="ms-auto small fw-normal">Manage</a>
            </div>
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
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= url('/student/my-bookings.php?b=' . (int)$b['id']) ?>">View</a></td>
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
            <div class="card-header-sc">
                <i class="bi bi-bell text-danger"></i> Notifications
                <a href="<?= url('/student/notifications.php') ?>" class="ms-auto small fw-normal">All</a>
            </div>
            <div class="p-3">
                <?php if (!$notifications): ?>
                    <p class="text-muted small mb-0 text-center py-3">No notifications yet.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                    <div class="d-flex gap-2 mb-3 <?= !$n['is_read'] ? '' : 'opacity-50' ?>">
                        <i class="bi bi-bell text-<?= $n['is_read'] ? 'muted' : 'primary' ?>"></i>
                        <div class="small">
                            <div class="fw-semibold"><?= e($n['title']) ?></div>
                            <div class="text-muted"><?= e(mb_strimwidth($n['message'], 0, 80, '...')) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= date('d M, g:i A', strtotime($n['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sc-card">
            <div class="card-header-sc">
                <i class="bi bi-megaphone text-warning"></i> Announcements
            </div>
            <div class="p-3">
                <?php if (!$announcements): ?>
                    <p class="text-muted small mb-0 text-center py-3">No announcements.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                    <div class="insight-card mb-2">
                        <div class="fw-semibold small"><?= e($a['title']) ?></div>
                        <div class="text-muted small"><?= e(mb_strimwidth($a['message'], 0, 100, '...')) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dash_footer.php'; ?>
