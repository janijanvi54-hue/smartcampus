<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'Resource Management';
$active = 'resources';

// Counts for filter chips
$typeCounts = [];
foreach ($pdo->query("SELECT type, COUNT(*) c FROM resources GROUP BY type") as $r) $typeCounts[$r['type']] = (int)$r['c'];

$resources = $pdo->query("SELECT * FROM resources ORDER BY type, name")->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <div class="input-group input-group-sm" style="max-width:320px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="resSearch" placeholder="Search resources...">
    </div>
    <select class="form-select form-select-sm" id="resTypeFilter" style="width:auto">
        <option value="">All types</option>
        <?php foreach (RESOURCE_TYPES as $key => $label): ?>
            <option value="<?= $key ?>"><?= $label ?> (<?= (int)($typeCounts[$key] ?? 0) ?>)</option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#resourceModal" onclick="openResourceModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Resource
    </button>
</div>

<div class="sc-card">
    <div class="table-responsive">
        <table class="table sc-table mb-0" id="resourceTable">
            <thead><tr><th>Name</th><th>Type</th><th>Capacity</th><th>Location</th><th>Bookable by</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($resources as $r): ?>
                <tr data-type="<?= e($r['type']) ?>">
                    <td class="fw-semibold"><?= e($r['name']) ?></td>
                    <td><?= e(resource_type_label($r['type'])) ?></td>
                    <td><?= (int)$r['capacity'] ?></td>
                    <td><?= e($r['location']) ?></td>
                    <td><?= e(ucfirst($r['bookable_by'] ?? 'all')) ?></td>
                    <td>
                        <?php $rc = $r['status'] === 'active' ? 'success' : ($r['status'] === 'maintenance' ? 'warning' : 'secondary'); ?>
                        <span class="badge text-bg-<?= $rc ?>"><?= ucfirst($r['status']) ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" onclick='openResourceModal(<?= json_encode([
                            'id' => (int)$r['id'], 'name' => $r['name'], 'type' => $r['type'],
                            'capacity' => (int)$r['capacity'], 'location' => $r['location'],
                            'description' => $r['description'], 'facilities' => $r['facilities'], 'status' => $r['status'],
                            'bookable_by' => $r['bookable_by'] ?? 'all',
                        ]) ?>)'><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-<?= $r['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleResource(<?= (int)$r['id'] ?>)">
                            <i class="bi bi-<?= $r['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteResource(<?= (int)$r['id'] ?>)"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Resource modal -->
<div class="modal fade" id="resourceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resModalTitle">Add Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resourceForm">
                    <input type="hidden" name="id" id="resId">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Name</label>
                            <input type="text" class="form-control" name="name" id="resName" required placeholder="e.g. A101">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Type</label>
                            <select class="form-select" name="type" id="resType" required>
                                <?php foreach (RESOURCE_TYPES as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Capacity</label>
                            <input type="number" class="form-control" name="capacity" id="resCapacity" min="1" required placeholder="e.g. 50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" class="form-control" name="location" id="resLocation" required placeholder="e.g. Block A, Ground Floor">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" id="resDescription" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Facilities <small class="text-muted fw-normal">(comma separated)</small></label>
                            <input type="text" class="form-control" name="facilities" id="resFacilities" placeholder="Projector, AC, Wi-Fi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="resStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Who can book?</label>
                            <select class="form-select" name="bookable_by" id="resBookableBy">
                                <option value="all">Everyone (students &amp; faculty)</option>
                                <option value="student">Students only</option>
                                <option value="faculty">Faculty only</option>
                                <option value="admin">Admin only</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="resSaveBtn" onclick="saveResource()">Save Resource</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
