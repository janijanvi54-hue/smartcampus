<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Login';

if (current_user()) {
    redirect(role_home(current_user()['role']));
}

$errors = [];
$email = $_POST['email'] ?? '';
$return = $_POST['return'] ?? $_GET['return'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } else {
            $stmt = db()->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid email or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Your account has been deactivated. Please contact the administrator.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $token);
                    setcookie('sc_remember', $user['id'] . '.' . $hash, time() + 30 * 24 * 3600, '/', '', false, true);
                    file_put_contents(sys_get_temp_dir() . '/sc_remember_' . $user['id'] . '.txt', $hash);
                } else {
                    setcookie('sc_remember', '', time() - 3600, '/');
                }

                send_notification(db(), (int)$user['id'], 'New login', 'You signed in to DSVV SmartCampus. Welcome back!', 'info');
                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect(resolve_post_login_redirect($user, $return));
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="sc-auth-wrap py-5">
    <div class="container" style="max-width: 460px">
        <div class="sc-auth-card p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="sc-logo sc-logo-lg mx-auto mb-3" style="display:grid"><?= e(APP_NAME[0]) ?></span>
                <h1 class="h4 fw-bold mb-1">Welcome back</h1>
                <p class="text-muted mb-0">Log in to your <?= e(APP_NAME) ?> account</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= url('/login.php') ?>" id="loginForm" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="return" value="<?= e($return) ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" id="email"
                               value="<?= e($email) ?>" placeholder="you@smartcampus.local" required autofocus>
                    </div>
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="password"
                               placeholder="Your password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <a href="<?= url('/forgot-password.php') ?>" class="small">Forgot password?</a>
                </div>
                <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Don't have an account? <a href="<?= url('/register.php') ?>">Register here</a>
            </p>
        </div>

            <div class="text-center mt-4">
                <div class="sc-card p-3 small">
                    <div class="fw-semibold mb-2"><i class="bi bi-person-badge me-1"></i>Quick demo login (password: <code>Password123!</code>)</div>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark" data-demo-login="admin@smartcampus.local"><i class="bi bi-shield-lock me-1"></i>Admin</button>
                        <button type="button" class="btn btn-sm btn-outline-dark" data-demo-login="faculty@smartcampus.local"><i class="bi bi-person-workspace me-1"></i>Faculty</button>
                        <button type="button" class="btn btn-sm btn-outline-dark" data-demo-login="student@smartcampus.local"><i class="bi bi-mortarboard me-1"></i>Student</button>
                    </div>
                    <div class="text-muted mt-2 small">Click a role to fill the form, then press <strong>Log In</strong>.</div>
                </div>
            </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/auth.js') . '"></script>';
require_once __DIR__ . '/includes/footer.php';
?>
