<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'Booking Management';
$active = 'bookings';

$statuses = BOOKING_STATUSES;
$typeFilter = $_GET['type'] ?? '';

$where = [];
$params = [];
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $where[] = "(r.name LIKE :s OR u.name LIKE :s2 OR b.purpose LIKE :s3)";
    $like = "%{$search}%";
    $params[':s'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
}
if ($typeFilter !== '' && in_array($typeFilter, $statuses, true)) {
    $where[] = "b.status = :st";
    $params[':st'] = $typeFilter;
}

$sql = "SELECT b.*, r.name AS resource_name, r.type AS resource_type, r.location AS location,
               r.capacity AS capacity, u.name AS user_name, u.email AS user_email, u.role AS user_role
        FROM bookings b
        JOIN resources r ON r.id = b.resource_id
        JOIN users u ON u.id = b.user_id";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY FIELD(b.status,'pending','approved','rejected','cancelled','completed'), b.date DESC, b.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <form class="input-group input-group-sm" style="max-width:320px" method="get">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search resource / user...">
    </form>
    <select class="form-select form-select-sm" style="width:auto" onchange="location.href='?type='+this.value">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $typeFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-success" onclick="bulkStatus('approved')"><i class="bi bi-check2-all me-1"></i>Approve Pending</button>
        <button class="btn btn-sm btn-outline-danger" onclick="bulkStatus('rejected')"><i class="bi bi-x-octagon me-1"></i>Reject Pending</button>
    </div>
</div>

<div class="sc-card">
    <div class="table-responsive">
        <table class="table sc-table mb-0">
            <thead><tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>#</th><th>Resource</th><th>User</th><th>Date</th><th>Time</th><th>Users</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if (!$bookings): ?>
                <tr><td colspan="9" class="text-center text-muted py-5">No bookings found.</td></tr>
            <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                <tr data-booking="<?= (int)$b['id'] ?>">
                    <td><input type="checkbox" class="row-check" value="<?= (int)$b['id'] ?>"></td>
                    <td><?= (int)$b['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($b['resource_name']) ?></div>
                        <small class="text-muted"><?= e(resource_type_label($b['resource_type'])) ?></small>
                    </td>
                    <td>
                        <div><?= e($b['user_name']) ?></div>
                        <small class="text-muted"><?= e($b['user_role']) ?></small>
                    </td>
                    <td><?= fmt_date($b['date']) ?></td>
                    <td><?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></td>
                    <td><?= (int)$b['expected_users'] ?>/<?= (int)$b['capacity'] ?></td>
                    <td><span class="badge text-bg-<?= booking_status_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" title="Approve" onclick="setBookingStatus(<?= (int)$b['id'] ?>, 'approved')"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-sm btn-outline-danger" title="Reject" onclick="setBookingStatus(<?= (int)$b['id'] ?>, 'rejected')"><i class="bi bi-x-lg"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" title="Cancel" onclick="setBookingStatus(<?= (int)$b['id'] ?>, 'cancelled')"><i class="bi bi-x-circle"></i></button>
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
