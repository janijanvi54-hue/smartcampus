<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_any_role(['student', 'faculty']);
$pdo = db();

$page_title = 'Report a Problem';
$active = 'report';

$resources = $pdo->query("SELECT id, name, type FROM resources WHERE status = 'active' ORDER BY type, name")->fetchAll();
$categories = ['Projector', 'Computer', 'Internet/Wi-Fi', 'AC', 'Lights', 'Furniture', 'Cleanliness', 'Other'];
$priorities = ['low', 'medium', 'high', 'critical'];

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-wrench-adjustable text-danger"></i> Report a resource problem</div>
            <div class="p-4">
                <form id="complaintForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Resource</label>
                            <select class="form-select" name="resource_id" required>
                                <option value="0">-- Select a resource --</option>
                                <?php foreach ($resources as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>"><?= e(resource_type_label($r['type'])) ?> - <?= e($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Problem category</label>
                            <select class="form-select" name="category" required>
                                <option value="">-- Select category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= e($c) ?>"><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Describe the problem</label>
                            <textarea class="form-control" name="description" rows="4" required minlength="10"
                                      placeholder="e.g. The projector in this room is not displaying properly and the bulb seems to be damaged."></textarea>
                            <div class="form-text">Include as much detail as possible so the facilities team can act quickly.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority</label>
                            <select class="form-select" name="priority">
                                <?php foreach ($priorities as $p): ?>
                                    <option value="<?= $p ?>" <?= $p === 'medium' ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Photo attachment <small class="text-muted fw-normal">(optional)</small></label>
                            <input type="file" class="form-control" name="attachment" accept="image/*" disabled>
                            <div class="form-text text-muted">File upload is a placeholder in this demo build.</div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit" id="complaintBtn"><i class="bi bi-send me-2"></i>Submit Report</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-list-check text-info"></i> My recent reports</div>
            <div class="p-3" id="myComplaints">
                <div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm mb-2"></div><div class="small">Loading...</div></div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/booking.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
