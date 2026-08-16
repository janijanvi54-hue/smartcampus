<?php
/**
 * /api/notifications.php
 * GET  -> list current user's notifications
 * POST { action: 'read', notification_id } | { action: 'read_all' }
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT :lim");
    $stmt->bindValue(':uid', (int)$u['id'], PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $unread = 0;
    foreach ($rows as $r) if (!(int)$r['is_read']) $unread++;

    json_response(['success' => true, 'unread' => $unread, 'notifications' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_input();
    $action = $body['action'] ?? '';

    if ($action === 'read') {
        $id = (int)($body['notification_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => (int)$u['id']]);
        json_response(['success' => true, 'message' => 'Notification marked as read.']);
    }

    if ($action === 'read_all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
        $stmt->execute([':uid' => (int)$u['id']]);
        json_response(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    json_response(['error' => 'Unknown action.'], 400);
}

json_response(['error' => 'Method not allowed'], 405);
