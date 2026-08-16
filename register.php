<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Register';

if (current_user()) {
    redirect(role_home(current_user()['role']));
}

$errors = [];
$old = [
    'name' => '', 'email' => '', 'department' => '', 'user_identifier' => '', 'role' => 'student',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $old = [
            'name'            => trim($_POST['name'] ?? ''),
            'email'           => trim(strtolower($_POST['email'] ?? '')),
            'department'      => trim($_POST['department'] ?? ''),
            'user_identifier' => trim($_POST['user_identifier'] ?? ''),
            'role'            => $_POST['role'] ?? 'student',
        ];
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validation
        if (mb_strlen($old['name']) < 3) $errors[] = 'Full name must be at least 3 characters.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (mb_strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
        if (!in_array($old['role'], ['student', 'faculty'], true)) $errors[] = 'Please choose a valid role.';
        if ($old['department'] === '') $errors[] = 'Please select your department.';
        if ($old['user_identifier'] === '') $errors[] = 'Please enter your student / employee ID.';

        // Normal users must NOT be able to register as admin
        if (($old['role'] ?? '') === 'admin') $errors[] = 'Administrator accounts cannot be created via registration.';

        if (!$errors) {
            // Duplicate email / identifier prevention
            $stmt = db()->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $old['email']]);
            if ($stmt->fetch()) $errors[] = 'This email is already registered. Please log in.';

            $stmt = db()->prepare("SELECT id FROM users WHERE user_identifier = :ui LIMIT 1");
            $stmt->execute([':ui' => $old['user_identifier']]);
            if ($stmt->fetch()) $errors[] = 'This student / employee ID is already registered.';
        }

        if (!$errors) {
            $stmt = db()->prepare("INSERT INTO users (name, email, password, role, department, user_identifier, status)
                                   VALUES (:n, :e, :p, :r, :d, :ui, 'active')");
            $stmt->execute([
                ':n'  => $old['name'],
                ':e'  => $old['email'],
                ':p'  => password_hash($password, PASSWORD_DEFAULT),
                ':r'  => $old['role'],
                ':d'  => $old['department'],
                ':ui' => $old['user_identifier'],
            ]);
            $userId = (int)db()->lastInsertId();

            send_notification(db(), $userId, 'Welcome to DSVV SmartCampus!',
                'Your account has been created. You can now discover and book campus resources.', 'info');

            set_flash('success', 'Registration successful! Please log in.');
            redirect('/login.php');
        }
    }
}

$departments = [
    'Computer Science', 'Information Technology', 'Data Science', 'Yoga & Yogic Science',
    'Ayurveda', 'Sanskrit', 'Yoga Therapy', 'Psychology', 'Indian Culture & Tourism',
    'Naturopathy', 'Mathematics', 'Physics', 'Chemistry', 'Business Administration', 'Other',
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="sc-auth-wrap py-5">
    <div class="container" style="max-width: 620px">
        <div class="sc-auth-card p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="sc-logo sc-logo-lg mx-auto mb-3" style="display:grid"><?= e(APP_NAME[0]) ?></span>
                <h1 class="h4 fw-bold mb-1">Create your account</h1>
                <p class="text-muted mb-0">Join <?= e(APP_UNIVERSITY) ?> as a student or faculty member</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= url('/register.php') ?>" id="registerForm" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" name="name" id="regName" value="<?= e($old['name']) ?>" placeholder="e.g. Riya Sharma" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" name="email" id="regEmail" value="<?= e($old['email']) ?>" placeholder="you@smartcampus.local" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Student / Employee ID</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                            <input type="text" class="form-control" name="user_identifier" id="regUi" value="<?= e($old['user_identifier']) ?>" placeholder="e.g. STU2026001" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Department</label>
                        <select class="form-select" name="department" id="regDept" required>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= e($d) ?>" <?= $old['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role</label>
                        <div class="d-grid gap-2">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="role" id="roleStudent" value="student" <?= $old['role'] === 'student' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary" for="roleStudent"><i class="bi bi-mortarboard me-1"></i>Student</label>
                                <input type="radio" class="btn-check" name="role" id="roleFaculty" value="faculty" <?= $old['role'] === 'faculty' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary" for="roleFaculty"><i class="bi bi-person-workspace me-1"></i>Faculty</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" name="password" id="regPassword" placeholder="Min 8 characters" required>
                            <button class="btn btn-outline-secondary" type="button" data-toggle-pass="regPassword" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" name="confirm_password" id="regConfirm" placeholder="Repeat password" required>
                            <button class="btn btn-outline-secondary" type="button" data-toggle-pass="regConfirm" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-shield-lock me-1"></i> Administrator accounts cannot be created here. Admin accounts are provisioned by the system administrator only.
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit" id="registerBtn">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </button>
                    </div>
                </div>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Already have an account? <a href="<?= url('/login.php') ?>">Log in</a>
            </p>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/auth.js') . '"></script>';
require_once __DIR__ . '/includes/footer.php';
?>
