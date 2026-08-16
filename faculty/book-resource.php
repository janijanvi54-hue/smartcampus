<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('faculty', '/login.php');
$pdo = db();

$page_title = 'Book Resource';
$active = 'book';

$presetResource = (int)($_GET['resource_id'] ?? 0);
$resources = $pdo->query("SELECT id, name, type, capacity, location FROM resources WHERE status = 'active' ORDER BY type, name")->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-ui-checks text-primary"></i> Step 1 - Requirements</div>
            <div class="p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Resource type</label>
                    <select class="form-select" id="rcType">
                        <?php foreach (RESOURCE_TYPES as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" class="form-control" id="rcDate" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Start time</label>
                        <input type="time" class="form-control" id="rcStart" value="10:00">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">End time</label>
                        <input type="time" class="form-control" id="rcEnd" value="11:00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Number of students</label>
                    <input type="number" class="form-control" id="rcUsers" value="45" min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Preferred location (optional)</label>
                    <input type="text" class="form-control" id="rcLocation" placeholder="e.g. Block C">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Purpose</label>
                    <input type="text" class="form-control" id="rcPurpose" placeholder="e.g. Practical session">
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary" id="rcFindBtn"><i class="bi bi-magic me-2"></i>Find Best Resource</button>
                </div>
                <div class="form-text mt-2">The Smart Recommendation Engine checks availability, capacity and utilisation to suggest the best fit.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="sc-card" id="resultPanel">
            <div class="card-header-sc"><i class="bi bi-stars text-success"></i> Smart Recommendation Results</div>
            <div class="p-4">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-magic fs-1 d-block mb-3"></i>
                    Enter your requirements and click <strong>"Find Best Resource"</strong> to see intelligent suggestions.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/recommend.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
<script>
// pre-select the resource type + users when arriving from a pending booking
<?php if ($presetResource): ?>
document.addEventListener('DOMContentLoaded', () => {
    fetch('<?= url('/api/resources.php') ?>?limit=500')
        .then(r => r.json())
        .then(d => {
            const res = d.resources.find(x => x.id === <?= (int)$presetResource ?>);
            if (res) {
                document.getElementById('rcType').value = res.type;
                document.getElementById('rcFindBtn').click();
            }
        });
});
<?php endif; ?>
</script>
