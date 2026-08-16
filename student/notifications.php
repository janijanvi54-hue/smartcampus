<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_any_role(['student', 'faculty']);
$pdo = db();

$page_title = 'Notifications';
$active = 'notifications';

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT 100");
$stmt->execute([':uid' => (int)$u['id']]);
$notifications = $stmt->fetchAll();

$unread = unread_notifications($pdo, (int)$u['id']);

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="sc-card">
    <div class="card-header-sc">
        <i class="bi bi-bell text-danger"></i> Notifications
        <span class="badge bg-danger ms-1"><?= (int)$unread ?> unread</span>
        <?php if ($notifications): ?>
            <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="markAllRead()"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
        <?php endif; ?>
    </div>
    <div class="p-3">
        <?php if (!$notifications): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                No notifications yet.
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n):
                    $icon = match ($n['type']) {
                        'booking'        => ['calendar-check', 'primary'],
                        'recommendation' => ['magic', 'success'],
                        'maintenance'    => ['tools', 'warning'],
                        'announcement'   => ['megaphone', 'warning'],
                        'complaint'      => ['wrench-adjustable', 'danger'],
                        default          => ['info-circle', 'info'],
                    };
                ?>
                <div class="list-group-item d-flex gap-3 align-items-start py-3 <?= $n['is_read'] ? '' : 'bg-light' ?>" id="notif-<?= (int)$n['id'] ?>">
                    <div class="sc-cat-icon" style="width:42px;height:42px;font-size:1.1rem;background:var(--sc-navy-3)">
                        <i class="bi bi-<?= $icon[0] ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= e($n['title']) ?></strong>
                            <small class="text-muted text-nowrap"><?= date('d M, g:i A', strtotime($n['created_at'])) ?></small>
                        </div>
                        <p class="text-muted small mb-1"><?= e($n['message']) ?></p>
                        <span class="badge text-bg-<?= $icon[1] ?>"><?= ucfirst($n['type']) ?></span>
                        <?php if (!$n['is_read']): ?>
                            <button class="btn btn-sm btn-link p-0 ms-2" onclick="markRead(<?= (int)$n['id'] ?>)">Mark read</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/booking.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
