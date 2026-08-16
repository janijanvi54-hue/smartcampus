<?php
/**
 * Public resource browser (main frontend).
 * Shows all campus resources with live availability and utilisation.
 * No login required to browse; booking links are shown per role.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$u   = current_user();

$page_title = 'Browse Campus Resources';
$active     = 'resources';

$type   = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');
$date   = $_GET['date'] ?? date('Y-m-d');
$start  = $_GET['start'] ?? '08:00';
$end    = $_GET['end'] ?? '21:00';

$typeValid = $type !== '' && isset(RESOURCE_TYPES[$type]);
if (!$typeValid) $type = '';

$sql = "SELECT * FROM resources WHERE status = 'active'";
$params = [];
if ($typeValid) {
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
$sql .= " ORDER BY type, name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$resources = [];
foreach ($rows as $r) {
    $res = resource_with_utilization($pdo, $r);
    $freeSlots = 0;
    for ($h = 8; $h < 21; $h++) {
        if (is_resource_available($pdo, (int)$res['id'], $date, sprintf('%02d:00', $h), sprintf('%02d:00', $h + 1))) $freeSlots++;
    }
    $res['free']       = is_resource_available($pdo, (int)$res['id'], $date, $start, $end);
    $res['free_slots'] = $freeSlots;
    $resources[] = $res;
}

$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE status='active'")->fetchColumn();

// Booking CTA: login is mandatory; each role lands on its own booking flow.
if (!$u) {
    $bookHref  = url('/login.php');
    $bookLabel = 'Login to book';
} else {
    $bookHref = url(match ($u['role']) {
        'admin'   => '/admin/resources.php',
        'faculty' => '/faculty/book-resource.php',
        default   => '/student/resources.php',
    });
    $bookLabel = $u['role'] === 'admin' ? 'Manage resources' : 'Book a resource';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row align-items-end mb-4">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-1">Browse Campus Resources</h1>
            <p class="text-muted mb-0"><?= count($resources) ?> of <?= $totalCount ?> resources &middot; live availability, utilisation and allocation for <?= e(fmt_date($date)) ?>. No login needed to browse.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="<?= $bookHref ?>" class="btn btn-gold"><i class="bi bi-calendar-plus me-1"></i><?= e($bookLabel) ?></a>
        </div>
    </div>

    <!-- Quick type filter -->
    <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="<?= url('/resources.php' . ($search ? '?search=' . rawurlencode($search) : '')) ?>" class="btn btn-sm <?= $type === '' ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
        <?php foreach (RESOURCE_TYPES as $key => $label): ?>
            <a href="<?= url('/resources.php?type=' . $key) ?>" class="btn btn-sm <?= $type === $key ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Filter form -->
    <form method="get" class="sc-card p-3 mb-4">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Name, location, facilities...">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Type</label>
                <select class="form-select" name="type">
                    <option value="">All types</option>
                    <?php foreach (RESOURCE_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" class="form-control" name="date" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-3 col-md-1">
                <label class="form-label small fw-semibold mb-1">Start</label>
                <input type="time" class="form-control" name="start" value="<?= e($start) ?>">
            </div>
            <div class="col-3 col-md-1">
                <label class="form-label small fw-semibold mb-1">End</label>
                <input type="time" class="form-control" name="end" value="<?= e($end) ?>">
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </div>
    </form>

    <?php if (empty($resources)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search fs-1 d-block mb-2"></i>
            No resources match your filters.
            <div class="mt-3"><a href="<?= url('/resources.php') ?>" class="btn btn-outline-primary btn-sm">Clear filters</a></div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($resources as $res): ?>
                <?php
                $cls = $res['utilization_class'];
                $barColor = match ($cls['color']) { 'success' => '#16a34a', 'warning' => '#f59e0b', 'danger' => '#dc2626', 'info' => '#0ea5e9' };
                $bookableBy = match ($res['bookable_by'] ?? 'all') { 'student' => 'Students', 'faculty' => 'Faculty', 'admin' => 'Admin', default => 'All users' };
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="sc-resource-card h-100 d-flex flex-column">
                        <div class="rc-head d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h6 class="fw-bold mb-0"><?= e($res['name']) ?></h6>
                                <small class="text-muted"><?= e(resource_type_label($res['type'])) ?></small>
                            </div>
                            <span class="badge text-bg-<?= $res['free'] ? 'success' : 'secondary' ?>"><?= $res['free'] ? 'Available' : 'Occupied' ?></span>
                        </div>
                        <div class="rc-body flex-grow-1 d-flex flex-column">
                            <div class="d-flex gap-2 flex-wrap small text-muted mb-2">
                                <span><i class="bi bi-geo-alt me-1"></i><?= e($res['location']) ?></span>
                                <span><i class="bi bi-people me-1"></i><?= (int)$res['capacity'] ?> seats</span>
                            </div>
                            <div class="small mb-2"><span class="badge text-bg-light border"><i class="bi bi-person-badge me-1"></i>Bookable by: <?= e($bookableBy) ?></span></div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Utilisation (30d)</span>
                                <strong class="text-<?= $cls['color'] ?>"><span class="sc-dot dot-<?= $cls['dot'] ?>"></span><?= e($cls['label']) ?> <?= (float)$res['avg_utilization'] ?>%</strong>
                            </div>
                            <div class="util-bar mb-2"><span style="width:<?= min(100, (float)$res['avg_utilization']) ?>%;background:<?= $barColor ?>"></span></div>
                            <div class="small text-muted mb-3">
                                <i class="bi bi-calendar-check me-1"></i><strong><?= (int)$res['free_slots'] ?></strong> free hourly slots on <?= e(fmt_date($date)) ?>
                            </div>
                            <a class="btn btn-sm btn-primary w-100 mt-auto" href="<?= url('/resource-details.php?id=' . (int)$res['id']) ?>"><i class="bi bi-info-circle me-1"></i>View Details &amp; Availability</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
