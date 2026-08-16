<?php
/**
 * Dashboard layout header (sidebar + topbar).
 * Requires the current user via includes/auth.php.
 */
if (!isset($u) || !$u) $u = current_user();
$pageTitle = $page_title ?? 'Dashboard';
$activeNav = $active ?? '';
$unread = $u ? unread_notifications(db(), (int)$u['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="icon" href="<?= url('/assets/images/favicon.svg') ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body class="dash-body">

<div class="dash-shell">
    <!-- Sidebar -->
    <aside class="sc-sidebar" id="sidebar">
        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom sc-sidebar-brand">
            <span class="sc-logo"><?= e(APP_NAME[0]) ?></span>
            <div class="flex-grow-1">
                <div class="fw-bold sc-brand-text text-white"><?= e(APP_NAME) ?></div>
                <small class="text-white-50"><?= ucfirst(e($u['role'])) ?> Portal</small>
            </div>
            <button class="btn btn-sm btn-link text-white d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Close sidebar">
                <i class="bi bi-arrow-bar-left"></i>
            </button>
        </div>

        <nav class="sc-sidebar-nav flex-grow-1 overflow-auto">
            <?php
            $navItems = [
                'student' => [
                    ['label' => 'Dashboard',        'icon' => 'speedometer2',  'href' => '/student/dashboard.php',         'key' => 'dashboard'],
                    ['label' => 'Available Resources','icon' => 'building',    'href' => '/student/resources.php',        'key' => 'resources'],
                    ['label' => 'My Bookings',      'icon' => 'calendar-check', 'href' => '/student/my-bookings.php',      'key' => 'bookings'],
                    ['label' => 'Notifications',    'icon' => 'bell',          'href' => '/student/notifications.php',    'key' => 'notifications', 'badge' => $unread],
                    ['label' => 'Report a Problem', 'icon' => 'wrench-adjustable','href' => '/student/report-problem.php','key' => 'report'],
                ],
                'faculty' => [
                    ['label' => 'Dashboard',        'icon' => 'speedometer2',  'href' => '/faculty/dashboard.php',        'key' => 'dashboard'],
                    ['label' => 'Book Resource',    'icon' => 'calendar-plus', 'href' => '/faculty/book-resource.php',    'key' => 'book'],
                    ['label' => 'My Bookings',      'icon' => 'calendar-check','href' => '/faculty/my-bookings.php',     'key' => 'bookings'],
                    ['label' => 'Notifications',    'icon' => 'bell',          'href' => '/faculty/dashboard.php?tab=notifications','key' => 'notifications','badge' => $unread],
                    ['label' => 'Report a Problem', 'icon' => 'wrench-adjustable','href' => '/student/report-problem.php','key' => 'report'],
                ],
                'admin' => [
                    ['label' => 'Dashboard',        'icon' => 'speedometer2',  'href' => '/admin/dashboard.php',         'key' => 'dashboard'],
                    ['label' => 'Resources',        'icon' => 'building',      'href' => '/admin/resources.php',          'key' => 'resources'],
                    ['label' => 'Bookings',         'icon' => 'calendar-check', 'href' => '/admin/bookings.php',          'key' => 'bookings'],
                    ['label' => 'Users',            'icon' => 'people',        'href' => '/admin/users.php',              'key' => 'users'],
                    ['label' => 'Complaints',       'icon' => 'wrench-adjustable','href' => '/admin/complaints.php',      'key' => 'complaints'],
                    ['label' => 'Announcements',    'icon' => 'megaphone',     'href' => '/admin/announcements.php',      'key' => 'announcements'],
                    ['label' => 'Analytics',        'icon' => 'graph-up',      'href' => '/admin/analytics.php',          'key' => 'analytics'],
                ],
            ];
            foreach ($navItems[$u['role']] ?? [] as $item):
                $activeClass = $activeNav === $item['key'] ? ' active' : '';
            ?>
            <a href="<?= url($item['href']) ?>" class="sc-nav-item<?= $activeClass ?>">
                <i class="bi bi-<?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
                <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                    <span class="badge bg-danger ms-auto"><?= (int)$item['badge'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-3 border-top">
            <div class="d-flex align-items-center gap-2">
                <div class="sc-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
                <div class="flex-grow-1 text-truncate">
                    <div class="text-white small fw-semibold text-truncate"><?= e($u['name']) ?></div>
                    <div class="text-white-50 small"><?= e($u['email']) ?></div>
                </div>
                <a href="<?= url('/logout.php') ?>" class="btn btn-sm btn-outline-light" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </aside>

    <!-- Main area -->
    <div class="dash-main">
        <div class="sc-topbar d-flex align-items-center gap-3 px-3 px-lg-4">
            <button class="btn btn-light btn-sm d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"><i class="bi bi-list"></i></button>
            <div class="flex-grow-1">
                <h1 class="h5 mb-0 fw-bold"><?= e($pageTitle) ?></h1>
            </div>
            <a href="<?= url('/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Public site"><i class="bi bi-globe2"></i><span class="d-none d-sm-inline ms-1">Site</span></a>
            <a href="<?= url(role_home($u['role'])) ?>" class="btn btn-sm btn-light" title="Home"><i class="bi bi-house"></i></a>
        </div>
        <main class="dash-content">
