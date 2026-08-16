<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_any_role(['student', 'faculty']);
$pdo = db();

$page_title = 'Available Resources';
$active = 'resources';

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="mb-3">
    <p class="text-muted mb-2">Search and filter campus resources. Live availability and utilisation are computed from real usage data.</p>
    <div class="sc-card p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="fSearch" placeholder="Name, location...">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Type</label>
                <select class="form-select form-select-sm" id="fType">
                    <option value="">All types</option>
                    <?php foreach (RESOURCE_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" class="form-control form-control-sm" id="fDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-3 col-md-1">
                <label class="form-label small fw-semibold mb-1">Start</label>
                <input type="time" class="form-control form-control-sm" id="fStart" value="08:00">
            </div>
            <div class="col-3 col-md-1">
                <label class="form-label small fw-semibold mb-1">End</label>
                <input type="time" class="form-control form-control-sm" id="fEnd" value="21:00">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold mb-1">Capacity</label>
                <input type="number" class="form-control form-control-sm" id="fCap" placeholder="Any" min="1">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select form-select-sm" id="fStatus">
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="crowded">Overcrowded</option>
                    <option value="under">Under-utilized</option>
                    <option value="high">High utilization</option>
                    <option value="booked">Occupied</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row g-3" id="resourceGrid">
    <div class="col-12 text-center py-5 text-muted">
        <div class="spinner-border text-primary mb-3"></div>
        <div>Loading resources...</div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/booking.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
