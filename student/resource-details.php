<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_any_role(['student', 'faculty']);
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
$stmt->execute([':id' => $id]);
$resource = $stmt->fetch();

if (!$resource) {
    set_flash('danger', 'Resource not found.');
    redirect('/student/resources.php');
}

$page_title = $resource['name'] . ' - Book Resource';
$active = 'resources';

$res = resource_with_utilization($pdo, $resource);
$type = $resource['type'];
$bookUrl = $u['role'] === 'faculty' ? '/faculty/book-resource.php?resource_id=' . $id : null;
$bookable = can_role_book($u['role'], $resource['bookable_by'] ?? 'all');

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="sc-card">
            <div class="card-header-sc">
                <i class="bi bi-building text-primary"></i> <?= e($resource['name']) ?>
                <span class="badge text-bg-<?= $res['utilization_class']['color'] ?> ms-auto">
                    <span class="sc-dot dot-<?= $res['utilization_class']['dot'] ?>"></span><?= e($res['utilization_class']['label']) ?>
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
                        <div class="fw-semibold"><?= (int)$resource['capacity'] ?></div>
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
                        <span class="text-muted">Average utilisation</span>
                        <strong><?= (float)$res['avg_utilization'] ?>%</strong>
                    </div>
                    <div class="util-bar">
                        <span style="width:<?= min(100, (float)$res['avg_utilization']) ?>%;background:<?= match ($res['utilization_class']['color']) {
                            'success' => '#16a34a', 'warning' => '#f59e0b', 'danger' => '#dc2626', 'info' => '#0ea5e9'
                        } ?>"></span>
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
        <?php if (!$bookable): ?>
            <div class="sc-card mb-4">
                <div class="card-header-sc"><i class="bi bi-shield-lock text-warning"></i> Booking</div>
                <div class="p-4">
                    <div class="alert alert-warning mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>This resource is managed by the administration and is not
                        bookable by <?= e($u['role']) ?> users.
                        <?php if ($u['role'] === 'faculty'): ?>
                            <a href="<?= url('/faculty/book-resource.php') ?>" class="alert-link">Book a different resource</a>.
                        <?php else: ?>
                            <a href="<?= url('/student/resources.php') ?>" class="alert-link">Browse bookable resources</a>.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
        <div class="sc-card mb-4">
            <div class="card-header-sc"><i class="bi bi-calendar-check text-success"></i> Book this resource</div>
            <div class="p-4">
                <input type="hidden" id="bkResourceId" value="<?= (int)$resource['id'] ?>">
                <input type="hidden" id="bkCapacity" value="<?= (int)$resource['capacity'] ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Date</label>
                    <input type="date" class="form-control" id="bkDate" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Start time</label>
                        <input type="time" class="form-control" id="bkStart" value="10:00">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">End time</label>
                        <input type="time" class="form-control" id="bkEnd" value="11:00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Number of expected users</label>
                    <input type="number" class="form-control" id="bkUsers" value="<?= min((int)$resource['capacity'], 20) ?>" min="1" max="<?= (int)$resource['capacity'] ?>">
                    <div class="form-text">Capacity of this resource: <?= (int)$resource['capacity'] ?> users.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Purpose</label>
                    <input type="text" class="form-control" id="bkPurpose" placeholder="e.g. Group study, class revision...">
                </div>

                <div id="bkFeedback"></div>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" id="bkCheckBtn"><i class="bi bi-check2-circle me-1"></i>Check Availability</button>
                    <button class="btn btn-primary" id="bkSubmitBtn" disabled><i class="bi bi-calendar-plus me-1"></i>Request Booking</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-clock-history text-info"></i> Available time slots (<?= fmt_date($_GET['date'] ?? date('Y-m-d')) ?>)</div>
            <div class="p-3">
                <div class="d-flex flex-wrap gap-2" id="slotGrid">
                    <span class="text-muted small">Loading slots...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/booking.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
