<?php
/**
 * Public page header + navbar.
 * Expected: $page_title (string), optional $active (nav key).
 * Requires: config/session.php + includes/auth.php (for current_user()).
 */
if (empty($page_title)) $page_title = APP_NAME;
$u = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
    <link rel="icon" href="<?= url('/assets/images/favicon.svg') ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body class="public-body">

<nav class="navbar navbar-expand-lg navbar-light sc-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/index.php') ?>">
            <span class="sc-logo"><?= APP_NAME[0] ?></span>
            <span class="fw-bold sc-brand-text"><?= e(APP_NAME) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'home' ? 'active' : '' ?>" href="<?= url('/index.php') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'about' ? 'active' : '' ?>" href="<?= url('/about.php') ?>">About</a></li>
                <li class="nav-item"><a class="nav-link <?= ($active ?? '') === 'resources' ? 'active' : '' ?>" href="<?= url('/resources.php') ?>">Resources</a></li>
                <li class="nav-item d-lg-none"><hr class="dropdown-divider"></li>
                <?php if ($u): ?>
                    <li class="nav-item"><a class="btn btn-sm btn-outline-primary me-2" href="<?= url(role_home($u['role'])) ?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-primary" href="<?= url('/logout.php') ?>"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-sm btn-outline-primary me-2" href="<?= url('/login.php') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-primary" href="<?= url('/register.php') ?>">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="min-vh-100">
