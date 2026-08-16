<?php
/**
 * Public resource details (main frontend).
 * Shows full resource information, live availability slots and the
 * allocation (booked/occupied slots) for a chosen date. No login required.
 * Logged-in students/faculty get a direct "Book this resource" action.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$u   = current_user();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
$stmt->execute([':id' => $id]);
$resource = $stmt->fetch();

if (!$resource) {
    set_flash('danger', 'Resource not found.');
    redirect('/resources.php');
}

$page_title = $resource['name'] . ' - Details & Availability';
$active     = 'resources';

$res  = resource_with_utilization($pdo, $resource);
$cls  = $res['utilization_class'];
$date = $_GET['date'] ?? date('Y-m-d');

// Hourly slots (view-only availability grid)
$slots = [];
$freeCount = 0;
for ($h = 8; $h < 21; $h++) {
    $s = sprintf('%02d:00', $h);
    $e = sprintf('%02d:00', $h + 1);
    $free = is_resource_available($pdo, $id, $date, $s, $e);
    if ($free) $freeCount++;
    $slots[] = [
        'label'     => date('g:i A', strtotime($s)) . ' - ' . date('g:i A', strtotime($e)),
        'available' => $free,
    ];
}

// Allocation for the selected date (all visible to the main frontend)
$stmt = $pdo->prepare(
    "SELECT b.start_time, b.end_time, b.purpose, b.status, b.expected_users, u.name AS user_name
     FROM bookings b JOIN users u ON u.id = b.user_id
     WHERE b.resource_id = :id AND b.date = :d AND b.status IN ('pending','approved')
     ORDER BY b.start_time"
);
$stmt->execute([':id' => $id, ':d' => $date]);
$allocations = $stmt->fetchAll();

// Role-aware booking CTA
$bookCta = null;
$bookNotice = null;
$bookable = can_role_book($u['role'] ?? '', $resource['bookable_by'] ?? 'all');
if (!$u) {
    $bookCta = ['href' => url('/login.php?return=/resource-details.php?id=' . $id), 'icon' => 'box-arrow-in-right', 'label' => 'Login to book this resource'];
} elseif ($u['role'] === 'admin') {
    $bookCta = ['href' => url('/admin/resources.php'), 'icon' => 'gear', 'label' => 'Manage resource (Admin)'];
} elseif ($bookable) {
    if ($u['role'] === 'faculty') {
        $bookCta = ['href' => url('/faculty/book-resource.php?resource_id=' . $id), 'icon' => 'calendar-plus', 'label' => 'Book this resource'];
    } else {
        $bookCta = ['href' => url('/student/resource-details.php?id=' . $id), 'icon' => 'calendar-plus', 'label' => 'Book this resource'];
    }
} else {
    $bookNotice = 'This resource is managed by the administration and is not bookable by ' . $u['role'] . ' users.';
}

$barColor = match ($cls['color']) { 'success' => '#16a34a', 'warning' => '#f59e0b', 'danger' => '#dc2626', 'info' => '#0ea5e9' };

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= url('/resources.php') ?>" class="text-decoration-none">Resources</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($resource['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="sc-card">
                <div class="card-header-sc">
                    <i class="bi bi-building text-primary"></i> <?= e($resource['name']) ?>
                    <span class="badge text-bg-<?= $cls['color'] ?> ms-auto">
                        <span class="sc-dot dot-<?= $cls['dot'] ?>"></span><?= e($cls['label']) ?>
                    </span>
                </div>
                <div class="p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted small">Type</div>
                            <div class="fw-semibold"><?= e(resource_type_label($resource['type'])) ?></div>
                        </div>
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted small">Capacity</div>
                            <div class="fw-semibold"><?= (int)$resource['capacity'] ?> users</div>
                        </div>
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted small">Occupancy</div>
                            <div class="fw-semibold"><?= (int)$res['avg_users'] ?> avg</div>
                        </div>
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted small">Utilisation</div>
                            <div class="fw-semibold"><?= (float)$res['avg_utilization'] ?>%</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Average utilisation (last 30 days)</span>
                            <strong><?= (float)$res['avg_utilization'] ?>%</strong>
                        </div>
                        <div class="util-bar">
                            <span style="width:<?= min(100, (float)$res['avg_utilization']) ?>%;background:<?= $barColor ?>"></span>
                        </div>
                    </div>

                    <h6 class="fw-bold"><i class="bi bi-geo-alt me-2 text-muted"></i>Location</h6>
                    <p class="text-muted"><?= e($resource['location']) ?></p>

                    <h6 class="fw-bold"><i class="bi bi-info-circle me-2 text-muted"></i>Description</h6>
                    <p class="text-muted"><?= e($resource['description'] ?: 'No description provided.') ?></p>

                    <h6 class="fw-bold"><i class="bi bi-tools me-2 text-muted"></i>Facilities</h6>
                    <div>
                        <?php foreach (explode(',', (string)$resource['facilities']) as $f): $f = trim($f); if ($f === '') continue; ?>
                            <span class="badge text-bg-light border me-1 mb-1"><i class="bi bi-check2-circle text-success me-1"></i><?= e($f) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <?php if ($bookCta): ?>
                <div class="sc-card mb-4">
                    <div class="card-header-sc"><i class="bi bi-calendar-check text-success"></i> Booking</div>
                    <div class="p-4">
                        <a href="<?= $bookCta['href'] ?>" class="btn btn-primary w-100"><i class="bi bi-<?= $bookCta['icon'] ?> me-1"></i><?= e($bookCta['label']) ?></a>
                        <div class="text-muted small mt-2">Requests are validated for conflicts and capacity, then approved by the administration.</div>
                    </div>
                </div>
            <?php elseif ($bookNotice): ?>
                <div class="sc-card mb-4">
                    <div class="card-header-sc"><i class="bi bi-shield-lock text-warning"></i> Booking</div>
                    <div class="p-4">
                        <div class="alert alert-warning mb-0 small"><i class="bi bi-info-circle me-1"></i><?= e($bookNotice) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sc-card mb-4">
                <div class="card-header-sc"><i class="bi bi-clock-history text-info"></i> Availability slots</div>
                <div class="p-3">
                    <form method="get" class="mb-3">
                        <input type="hidden" name="id" value="<?= (int)$resource['id'] ?>">
                        <div class="input-group input-group-sm">
                            <input type="date" class="form-control" name="date" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>">
                            <button class="btn btn-outline-primary" type="submit">Show</button>
                        </div>
                    </form>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($slots as $slot): ?>
                            <span class="btn btn-sm <?= $slot['available'] ? 'btn-outline-success' : 'btn-outline-secondary disabled' ?>"><?= e($slot['label']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="small text-muted mt-2">
                        <strong><?= $freeCount ?></strong> of 13 slots free on <?= e(fmt_date($date)) ?> &middot; green = free, grey = booked/pending.
                    </div>
                </div>
            </div>

            <div class="sc-card">
                <div class="card-header-sc"><i class="bi bi-list-check text-primary"></i> Allocation for <?= e(fmt_date($date)) ?></div>
                <div class="p-3">
                    <?php if (empty($allocations)): ?>
                        <p class="text-muted small mb-0">No bookings on this date &mdash; the resource is fully open.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($allocations as $a): ?>
                                <li class="d-flex gap-2 align-items-center border-bottom py-2">
                                    <span class="badge text-bg-<?= $a['status'] === 'approved' ? 'success' : 'warning' ?>"><?= e($a['status']) ?></span>
                                    <div class="flex-grow-1 small">
                                        <div class="fw-semibold"><?= e(substr($a['start_time'], 0, 5)) ?> &ndash; <?= e(substr($a['end_time'], 0, 5)) ?> <span class="text-muted">(<?= (int)$a['expected_users'] ?> users)</span></div>
                                        <div class="text-muted"><?= e($a['user_name']) ?> &middot; <?= e($a['purpose'] ?: 'No purpose') ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
