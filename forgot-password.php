<?php
/**
 * Forgot password - account recovery.
 * Verifies the user's email + student/employee ID, then allows a new password.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Forgot Password';

if (current_user()) redirect(role_home(current_user()['role']));

$errors = [];
$stage = $_POST['stage'] ?? 'verify';
$email = $_POST['email'] ?? '';
$identifier = $_POST['user_identifier'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = trim(strtolower($email));
        $identifier = trim($identifier);

        if ($stage === 'verify') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
            if ($identifier === '') $errors[] = 'Please enter your student / employee ID.';

            if (!$errors) {
                $stmt = db()->prepare("SELECT * FROM users WHERE email = :e AND user_identifier = :ui LIMIT 1");
                $stmt->execute([':e' => $email, ':ui' => $identifier]);
                $user = $stmt->fetch();
                if (!$user) {
                    $errors[] = 'No account matches those details. Please check and try again.';
                } else {
                    $stage = 'reset';
                }
            }
        } else {
            // stage === reset
            $newPass = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (mb_strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';
            if ($newPass !== $confirm) $errors[] = 'Password confirmation does not match.';

            if (!$errors) {
                $stmt = db()->prepare("SELECT * FROM users WHERE email = :e AND user_identifier = :ui LIMIT 1");
                $stmt->execute([':e' => $email, ':ui' => $identifier]);
                $user = $stmt->fetch();
                if (!$user) {
                    $errors[] = 'Account no longer exists.';
                } else {
                    $upd = db()->prepare("UPDATE users SET password = :p WHERE id = :id");
                    $upd->execute([':p' => password_hash($newPass, PASSWORD_DEFAULT), ':id' => (int)$user['id']]);
                    send_notification(db(), (int)$user['id'], 'Password changed', 'Your password was reset successfully.', 'info');
                    set_flash('success', 'Password updated! You can now log in.');
                    redirect('/login.php');
                }
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
                <h1 class="h4 fw-bold mb-1">Reset your password</h1>
                <p class="text-muted mb-0">Verify your identity to set a new password</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= url('/forgot-password.php') ?>" id="forgotForm" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="stage" value="<?= e($stage) ?>">

                <?php if ($stage === 'verify'): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email address</label>
                        <input type="email" class="form-control" name="email" value="<?= e($email) ?>" placeholder="you@smartcampus.local" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Student / Employee ID</label>
                        <input type="text" class="form-control" name="user_identifier" value="<?= e($identifier) ?>" placeholder="e.g. STU2026001" required>
                        <div class="form-text">Enter the ID used during registration to verify your identity.</div>
                    </div>
                    <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
                        <i class="bi bi-shield-check me-2"></i>Verify &amp; Continue
                    </button>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="fpPassword" placeholder="Min 8 characters" required>
                            <button class="btn btn-outline-secondary" type="button" data-toggle-pass="fpPassword" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" id="fpConfirm" placeholder="Repeat password" required>
                            <button class="btn btn-outline-secondary" type="button" data-toggle-pass="fpConfirm" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
                        <i class="bi bi-key me-2"></i>Reset Password
                    </button>
                <?php endif; ?>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                <a href="<?= url('/login.php') ?>"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
