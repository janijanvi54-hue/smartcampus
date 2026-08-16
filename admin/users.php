<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'User Management';
$active = 'users';

$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS total_bookings
        FROM users u";
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(u.name LIKE :s OR u.email LIKE :s2 OR u.user_identifier LIKE :s3 OR u.department LIKE :s4)";
    $like = "%{$search}%";
    $params[':s'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
}
if ($roleFilter !== '' && in_array($roleFilter, ['student', 'faculty', 'admin'], true)) {
    $where[] = "u.role = :r";
    $params[':r'] = $roleFilter;
}
if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'], true)) {
    $where[] = "u.status = :st";
    $params[':st'] = $statusFilter;
}
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY u.role, u.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roleCounts = [];
foreach ($pdo->query("SELECT role, COUNT(*) c FROM users GROUP BY role") as $r) $roleCounts[$r['role']] = (int)$r['c'];

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <form class="input-group input-group-sm" style="max-width:300px" method="get">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search name, email, ID...">
    </form>
    <select class="form-select form-select-sm" style="width:auto" onchange="location.href='?role='+this.value">
        <option value="">All roles</option>
        <?php foreach (['student', 'faculty', 'admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?> (<?= (int)($roleCounts[$r] ?? 0) ?>)</option>
        <?php endforeach; ?>
    </select>
    <select class="form-select form-select-sm" style="width:auto" onchange="location.href='?status='+this.value">
        <option value="">All statuses</option>
        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()"><i class="bi bi-person-plus me-1"></i>Add User</button>
</div>

<div class="sc-card">
    <div class="table-responsive">
        <table class="table sc-table mb-0">
            <thead><tr><th>User</th><th>Role</th><th>Department</th><th>ID</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $usr): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="sc-avatar"><?= e(strtoupper(substr($usr['name'], 0, 1))) ?></div>
                            <div>
                                <div class="fw-semibold"><?= e($usr['name']) ?></div>
                                <small class="text-muted"><?= e($usr['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge text-bg-<?= $usr['role'] === 'admin' ? 'dark' : ($usr['role'] === 'faculty' ? 'primary' : 'info') ?>"><?= ucfirst($usr['role']) ?></span></td>
                    <td><?= e($usr['department'] ?: '-') ?></td>
                    <td><code><?= e($usr['user_identifier'] ?: '-') ?></code></td>
                    <td><?= (int)$usr['total_bookings'] ?></td>
                    <td><span class="badge text-bg-<?= $usr['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($usr['status']) ?></span></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" onclick='openUserModal(<?= json_encode([
                            'id' => (int)$usr['id'], 'name' => $usr['name'], 'email' => $usr['email'],
                            'role' => $usr['role'], 'department' => $usr['department'], 'identifier' => $usr['user_identifier'],
                        ]) ?>)'><i class="bi bi-pencil"></i></button>
                        <?php if ((int)$usr['id'] !== (int)$u['id']): ?>
                            <button class="btn btn-sm btn-outline-<?= $usr['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleUser(<?= (int)$usr['id'] ?>)">
                                <i class="bi bi-<?= $usr['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" name="user_id" id="userId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Full name</label>
                            <input type="text" class="form-control" name="name" id="userName" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" id="userEmail" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Role</label>
                            <select class="form-select" name="role" id="userRole" onchange="toggleAdminWarning()">
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control" name="department" id="userDept">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student / Employee ID</label>
                            <input type="text" class="form-control" name="identifier" id="userIdentifier">
                        </div>
                        <div class="col-12 d-none" id="adminWarn">
                            <div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Admin accounts grant full system access.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveUser()">Save</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
