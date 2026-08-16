<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'Announcements';
$active = 'announcements';

$announcements = $pdo->query(
    "SELECT a.*, u.name AS author FROM announcements a JOIN users u ON u.id = a.created_by ORDER BY a.created_at DESC, a.id DESC"
)->fetchAll();

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="d-flex mb-3">
    <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#annModal" onclick="openAnnModal()">
        <i class="bi bi-plus-lg me-1"></i>New Announcement
    </button>
</div>

<div class="row g-3">
    <?php if (!$announcements): ?>
        <div class="col-12 text-center text-muted py-5">No announcements yet. Create one to notify everyone on campus.</div>
    <?php else: ?>
        <?php foreach ($announcements as $a): ?>
        <div class="col-lg-6">
            <div class="sc-card h-100">
                <div class="card-header-sc">
                    <i class="bi bi-megaphone text-warning"></i> <?= e($a['title']) ?>
                    <span class="badge text-bg-<?= $a['status'] === 'published' ? 'success' : 'secondary' ?> ms-auto"><?= ucfirst($a['status']) ?></span>
                </div>
                <div class="p-3">
                    <p class="text-muted small mb-3"><?= e($a['message']) ?></p>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted">
                            <i class="bi bi-person me-1"></i><?= e($a['author']) ?>
                            &middot; <?= date('d M Y, g:i A', strtotime($a['created_at'])) ?>
                        </span>
                        <span class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" onclick='openAnnModal(<?= json_encode([
                                'id' => (int)$a['id'], 'title' => $a['title'], 'message' => $a['message'], 'status' => $a['status'],
                            ]) ?>)'><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement(<?= (int)$a['id'] ?>)"><i class="bi bi-trash"></i></button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Announcement modal -->
<div class="modal fade" id="annModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="annModalTitle">New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="annForm">
                    <input type="hidden" name="id" id="annId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" class="form-control" name="title" id="annTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message</label>
                        <textarea class="form-control" name="message" id="annMessage" rows="4" required></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status" id="annStatus">
                            <option value="published">Publish now</option>
                            <option value="draft">Save as draft</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveAnnouncement()">Save</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/admin.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
