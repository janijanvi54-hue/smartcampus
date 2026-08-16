<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'Complaint Management';
$active = 'complaints';

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT c.*, r.name AS resource_name, u.name AS user_name, u.email AS user_email
        FROM complaints c
        LEFT JOIN resources r ON r.id = c.resource_id
        JOIN users u ON u.id = c.user_id";
$params = [];
if ($statusFilter !== '' && in_array($statusFilter, ['reported', 'in_progress', 'resolved'], true)) {
    $sql .= " WHERE c.status = :st";
    $params[':st'] = $statusFilter;
}
$sql .= " ORDER BY FIELD(c.status, 'reported', 'in_progress', 'resolved'), c.priority = 'critical' DESC, c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$priorityClass = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm">
        <a class="btn btn-outline-primary <?= $statusFilter === '' ? 'active' : '' ?>" href="?">All</a>
        <a class="btn btn-outline-warning <?= $statusFilter === 'reported' ? 'active' : '' ?>" href="?status=reported">Reported</a>
        <a class="btn btn-outline-info <?= $statusFilter === 'in_progress' ? 'active' : '' ?>" href="?status=in_progress">In Progress</a>
        <a class="btn btn-outline-success <?= $statusFilter === 'resolved' ? 'active' : '' ?>" href="?status=resolved">Resolved</a>
    </div>
</div>

<div class="sc-card">
    <div class="table-responsive">
        <table class="table sc-table mb-0">
            <thead><tr><th>#</th><th>Resource</th><th>Category</th><th>Priority</th><th>Reported By</th><th>Description</th><th>Status</th><th>Reported</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$complaints): ?>
                <tr><td colspan="9" class="text-center text-muted py-5">No complaints found.</td></tr>
            <?php else: ?>
                <?php foreach ($complaints as $c): ?>
                <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td class="fw-semibold"><?= e($c['resource_name'] ?: '-') ?></td>
                    <td><?= e($c['category']) ?></td>
                    <td><span class="badge text-bg-<?= $priorityClass[$c['priority']] ?>"><?= ucfirst($c['priority']) ?></span></td>
                    <td>
                        <div><?= e($c['user_name']) ?></div>
                        <small class="text-muted"><?= e($c['user_email']) ?></small>
                    </td>
                    <td style="max-width:260px"><div class="text-truncate" title="<?= e($c['description']) ?>"><?= e($c['description']) ?></div></td>
                    <td><span class="badge text-bg-<?= $c['status'] === 'resolved' ? 'success' : ($c['status'] === 'in_progress' ? 'info' : 'warning') ?>"><?= str_replace('_', ' ', ucfirst($c['status'])) ?></span></td>
                    <td><small><?= date('d M', strtotime($c['created_at'])) ?></small></td>
                    <td class="text-end text-nowrap">
                        <?php if ($c['status'] !== 'in_progress' && $c['status'] !== 'resolved'): ?>
                            <button class="btn btn-sm btn-outline-info" onclick="setComplaintStatus(<?= (int)$c['id'] ?>, 'in_progress')"><i class="bi bi-play-fill"></i></button>
                        <?php endif; ?>
                        <?php if ($c['status'] !== 'resolved'): ?>
                            <button class="btn btn-sm btn-outline-success" onclick="setComplaintStatus(<?= (int)$c['id'] ?>, 'resolved')"><i class="bi bi-check-lg"></i></button>
                        <?php endif; ?>
                        <?php if ($c['status'] === 'resolved'): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="setComplaintStatus(<?= (int)$c['id'] ?>, 'reported')"><i class="bi bi-arrow-counterclockwise"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
