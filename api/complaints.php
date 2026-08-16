<?php
/**
 * /api/complaints.php
 * GET  ?scope=mine|all&status=&category=   -> list complaints
 * POST { action: 'create', resource_id, category, description, priority }
 * POST { action: 'status', complaint_id, status }  (admin only)
 */
require_once __DIR__ . '/bootstrap.php';

$u = api_require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $scope = $_GET['scope'] ?? 'mine';
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';

    $sql = "SELECT c.*, r.name AS resource_name, u.name AS user_name
            FROM complaints c
            LEFT JOIN resources r ON r.id = c.resource_id
            JOIN users u ON u.id = c.user_id";
    $where = [];
    $params = [];

    if ($u['role'] === 'admin') {
        if ($scope === 'mine') { $where[] = "c.user_id = :uid"; $params[':uid'] = (int)$u['id']; }
    } else {
        $where[] = "c.user_id = :uid";
        $params[':uid'] = (int)$u['id'];
    }
    if ($status !== '' && in_array($status, ['reported', 'in_progress', 'resolved'], true)) {
        $where[] = "c.status = :st";
        $params[':st'] = $status;
    }
    if ($category !== '') {
        $where[] = "c.category = :cat";
        $params[':cat'] = $category;
    }
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY FIELD(c.status, 'reported', 'in_progress', 'resolved'), c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'count' => $stmt->rowCount(), 'complaints' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_input();
    $action = $body['action'] ?? '';

    if ($action === 'create') {
        $resourceId = (int)($body['resource_id'] ?? 0);
        $category   = trim($body['category'] ?? '');
        $description= trim($body['description'] ?? '');
        $priority   = in_array($body['priority'] ?? '', ['low', 'medium', 'high', 'critical'], true) ? $body['priority'] : 'medium';

        $allowedCat = ['Projector', 'Computer', 'Internet/Wi-Fi', 'AC', 'Lights', 'Furniture', 'Cleanliness', 'Other'];
        if (!in_array($category, $allowedCat, true)) json_response(['error' => 'Please choose a valid problem category.'], 400);
        if (mb_strlen($description) < 10) json_response(['error' => 'Please describe the problem in at least 10 characters.'], 400);

        if ($resourceId > 0) {
            $stmt = $pdo->prepare("SELECT id FROM resources WHERE id = :id");
            $stmt->execute([':id' => $resourceId]);
            if (!$stmt->fetch()) json_response(['error' => 'Resource not found.'], 404);
        }

        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, resource_id, category, description, priority, status)
                               VALUES (:u, :r, :c, :d, :p, 'reported')");
        $stmt->execute([':u' => (int)$u['id'], ':r' => $resourceId > 0 ? $resourceId : null, ':c' => $category, ':d' => $description, ':p' => $priority]);
        $id = (int)$pdo->lastInsertId();

        send_notification($pdo, (int)$u['id'], 'Complaint reported',
            "Your {$category} complaint has been logged and assigned. We will update you as it progresses.", 'complaint');
        // Notify all admins
        $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $a) {
            send_notification($pdo, (int)$a['id'], 'New complaint reported',
                "A new {$category} complaint (priority: {$priority}) has been reported by {$u['name']}.", 'complaint');
        }

        json_response(['success' => true, 'complaint_id' => $id, 'message' => 'Problem reported successfully. The facilities team has been notified.'], 201);
    }

    if ($action === 'status') {
        api_require_role('admin');
        $id = (int)($body['complaint_id'] ?? 0);
        $status = $body['status'] ?? '';
        if (!in_array($status, ['reported', 'in_progress', 'resolved'], true)) json_response(['error' => 'Invalid status.'], 400);

        $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $c = $stmt->fetch();
        if (!$c) json_response(['error' => 'Complaint not found.'], 404);

        $resolvedAt = $status === 'resolved' ? 'NOW()' : 'NULL';
        $stmt = $pdo->prepare("UPDATE complaints SET status = :s, resolved_at = " . $resolvedAt . " WHERE id = :id");
        $stmt->execute([':s' => $status, ':id' => $id]);

        send_notification($pdo, (int)$c['user_id'], 'Complaint update',
            "Your complaint ({$c['category']}) is now marked as {$status}.", 'complaint');

        json_response(['success' => true, 'message' => "Complaint marked as {$status}."]);
    }

    json_response(['error' => 'Unknown action.'], 400);
}

json_response(['error' => 'Method not allowed'], 405);
