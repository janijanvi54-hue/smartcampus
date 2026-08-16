<?php
/**
 * /api/announcements.php
 * GET  -> published announcements (any logged-in user)
 * POST { action: 'create'|'update'|'delete', ... }  (admin only)
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT a.*, u.name AS author FROM announcements a JOIN users u ON u.id = a.created_by
                         WHERE a.status = 'published' ORDER BY a.created_at DESC, a.id DESC");
    json_response(['success' => true, 'announcements' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    api_require_role('admin');
    $body = json_input();
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $title = trim($body['title'] ?? '');
        $message = trim($body['message'] ?? '');
        $status = ($body['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        if ($title === '' || $message === '') json_response(['error' => 'Title and message are required.'], 400);

        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, created_by, status) VALUES (:t, :m, :a, :s)");
        $stmt->execute([':t' => $title, ':m' => $message, ':a' => (int)$u['id'], ':s' => $status]);
        $id = (int)$pdo->lastInsertId();

        // Notify all active non-admin users when published
        if ($status === 'published') {
            $targets = $pdo->query("SELECT id FROM users WHERE status = 'active'")->fetchAll();
            foreach ($targets as $t) {
                send_notification($pdo, (int)$t['id'], 'Announcement', $title, 'announcement');
            }
        }
        json_response(['success' => true, 'announcement_id' => $id, 'message' => 'Announcement ' . ($status === 'published' ? 'published' : 'saved as draft') . '.'], 201);
    }

    if ($action === 'update') {
        $id = (int)($body['id'] ?? 0);
        $title = trim($body['title'] ?? '');
        $message = trim($body['message'] ?? '');
        $status = ($body['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        if ($title === '' || $message === '') json_response(['error' => 'Title and message are required.'], 400);

        $stmt = $pdo->prepare("UPDATE announcements SET title = :t, message = :m, status = :s WHERE id = :id");
        $stmt->execute([':t' => $title, ':m' => $message, ':s' => $status, ':id' => $id]);
        json_response(['success' => true, 'message' => 'Announcement updated.']);
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        json_response(['success' => true, 'message' => 'Announcement deleted.']);
    }

    json_response(['error' => 'Unknown action.'], 400);
}

json_response(['error' => 'Method not allowed'], 405);
