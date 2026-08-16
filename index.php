<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'DSVV Campus Resource & Facility Management';
$active = 'home';
$u = current_user();

// Live stats for the statistics section
try {
    $pdo = db();
    $stats = [
        'resources' => (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE status='active'")->fetchColumn(),
        'users'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
        'bookings'  => (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'complaints'=> (int)$pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn(),
    ];
} catch (Throwable $e) {
    $stats = ['resources' => 0, 'users' => 0, 'bookings' => 0, 'complaints' => 0];
}

$categories = [
    ['Auditoriums',     'auditorium',       'easel',            '#7c3aed'],
    ['Seminar Halls',   'seminar_hall',     'megaphone',        '#f59e0b'],
    ['Classrooms',      'classroom',        'easel',            '#2563eb'],
    ['Computer Labs',   'computer_lab',     'display',          '#16a34a'],
    ['Library',         'library',          'journal-bookmark', '#0ea5e9'],
    ['Study Spaces',    'study_room',       'book',             '#dc2626'],
    ['Meeting Rooms',   'meeting_room',     'people',           '#0f766e'],
    ['Hostels',         'hostel',           'house-door',       '#9333ea'],
    ['Cafeterias',      'canteen',          'cup-hot',          '#ea580c'],
    ['Health Centres',  'health_centre',    'heart-pulse',      '#dc2626'],
    ['Guest House',     'guest_house',      'building',         '#2563eb'],
    ['Amenities',       'amenity',          'tree',             '#16a34a'],
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="sc-hero py-5">
    <div class="sc-hero-bg" id="scHeroBg" aria-hidden="true"></div>
    <div class="container py-lg-5 py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-gold text-dark mb-3 px-3 py-2" style="background:var(--sc-gold);color:var(--sc-navy)">
                    <i class="bi bi-stars me-1"></i> <?= e(APP_CAMPUS) ?> &mdash; Campus Resource &amp; Facility Management
                </span>
                <h1 class="mt-2">Dev Sanskriti Vishwavidyalaya.<br><span class="hero-accent">Smarter Campus Resource Management.</span></h1>
                <p class="lead mt-3">Find, book and manage <?= (int)$stats['resources'] ?>+ campus resources &mdash; auditoriums, seminar halls, computer labs, hostels, health centres and more. Check live availability, avoid overcrowding, and let the system recommend the best alternative automatically.</p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="<?= url('/resources.php') ?>" class="btn btn-gold btn-lg px-4"><i class="bi bi-search me-2"></i>Find a Resource</a>
                    <a href="<?= $u ? url(role_home($u['role'])) : url('/login.php') ?>" class="btn btn-outline-white btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i><?= $u ? 'Go to Dashboard' : 'Login' ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="bg-white rounded-4 p-4 shadow-lg" style="color:var(--sc-text)">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0">Today's Resource Status</h6>
                        <span class="badge text-bg-success">Live</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span><i class="bi bi-easel me-2 text-purple"></i>Auditoriums</span>
                        <span class="badge text-bg-success"><span class="sc-dot dot-success"></span>Available</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span><i class="bi bi-display me-2 text-success"></i>Computer Labs</span>
                        <span class="badge text-bg-warning text-dark"><span class="sc-dot dot-warning"></span>High Utilisation</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span><i class="bi bi-journal-bookmark me-2 text-info"></i>Central Library</span>
                        <span class="badge text-bg-danger"><span class="sc-dot dot-danger"></span>Occupied</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span><i class="bi bi-house-door me-2 text-primary"></i>Hostels</span>
                        <span class="badge text-bg-info"><span class="sc-dot dot-info"></span>Occupied</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-book me-2 text-danger"></i>Study Spaces</span>
                        <span class="badge text-bg-info"><span class="sc-dot dot-info"></span>Under-utilised</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row text-center mt-5 g-3">
            <div class="col-6 col-md-3 sc-hero-stat"><div class="num"><?= (int)$stats['resources'] ?>+</div><small>Resources</small></div>
            <div class="col-6 col-md-3 sc-hero-stat"><div class="num"><?= (int)$stats['users'] ?>+</div><small>Active Users</small></div>
            <div class="col-6 col-md-3 sc-hero-stat"><div class="num"><?= (int)$stats['bookings'] ?>+</div><small>Bookings Made</small></div>
            <div class="col-6 col-md-3 sc-hero-stat"><div class="num"><?= (int)$stats['complaints'] ?>+</div><small>Issues Resolved</small></div>
        </div>
    </div>
</section>

<!-- Resource categories -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Browse by Resource Type</h2>
            <p class="text-muted">Everything your campus needs, in one place.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($categories as [$name, $key, $icon, $color]): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= url('/resources.php?type=' . $key) ?>" class="text-decoration-none text-reset">
                    <div class="sc-cat-card text-center">
                        <div class="sc-cat-icon mx-auto" style="background:<?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
                        <div class="fw-semibold"><?= $name ?></div>
                        <small class="text-muted">Browse now</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="py-5" style="background:#fff">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How DSVV SmartCampus Works</h2>
            <p class="text-muted">A complete workflow from campus data to smart booking.</p>
        </div>
        <div class="row g-4">
            <?php
            $steps = [
                ['Input campus data', 'Auditoriums, halls, labs, hostels, canteens and health centres are recorded with capacities.', 'database'],
                ['Check availability', 'Live availability and utilisation are computed for every resource.', 'search'],
                ['Analyse utilisation', 'Overcrowding and under-utilisation are detected automatically.', 'graph-up-arrow'],
                ['Get recommendations', 'The engine suggests the best alternative when a resource is unsuitable.', 'magic'],
                ['Book & manage', 'Bookings are validated, approved and tracked end to end.', 'calendar-check'],
                ['Monitor & report', 'Administration gets analytics, insights and exportable reports.', 'clipboard-data'],
            ];
            foreach ($steps as $i => [$title, $text, $icon]): ?>
            <div class="col-md-6 col-lg-4">
                <div class="sc-card p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="sc-step-num"><?= $i + 1 ?></span>
                        <i class="bi bi-<?= $icon ?> fs-3" style="color:var(--sc-navy)"></i>
                    </div>
                    <h6 class="fw-bold"><?= $title ?></h6>
                    <p class="text-muted mb-0 small"><?= $text ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Benefits -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="sc-card h-100 p-4">
                    <div class="sc-cat-icon mb-3" style="background:#2563eb"><i class="bi bi-mortarboard"></i></div>
                    <h5 class="fw-bold">For Students</h5>
                    <ul class="text-muted mb-0 small ps-3">
                        <li class="mb-2">Discover available study spaces &amp; labs instantly</li>
                        <li class="mb-2">Avoid overcrowded areas with live utilisation data</li>
                        <li class="mb-2">Book resources with one click</li>
                        <li class="mb-2">Track bookings and receive notifications</li>
                        <li class="mb-2">Report facility problems easily</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="sc-card h-100 p-4">
                    <div class="sc-cat-icon mb-3" style="background:#7c3aed"><i class="bi bi-person-workspace"></i></div>
                    <h5 class="fw-bold">For Faculty</h5>
                    <ul class="text-muted mb-0 small ps-3">
                        <li class="mb-2">Book rooms and labs for classes &amp; events</li>
                        <li class="mb-2">Smart recommendations when rooms are full</li>
                        <li class="mb-2">Capacity checks to match class strength</li>
                        <li class="mb-2">Reschedule and cancel easily</li>
                        <li class="mb-2">Never double-book a venue again</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="sc-card h-100 p-4">
                    <div class="sc-cat-icon mb-3" style="background:#0ea5e9"><i class="bi bi-diagram-3"></i></div>
                    <h5 class="fw-bold">For Administration</h5>
                    <ul class="text-muted mb-0 small ps-3">
                        <li class="mb-2">Central dashboard for all resources &amp; bookings</li>
                        <li class="mb-2">Utilisation analytics &amp; smart insights</li>
                        <li class="mb-2">Detect overcrowding &amp; under-utilisation</li>
                        <li class="mb-2">Manage complaints &amp; publish announcements</li>
                        <li class="mb-2">Export reports to CSV</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 sc-hero">
    <div class="container text-center py-4">
        <h2 class="fw-bold">Ready to manage your campus smarter?</h2>
        <p class="lead text-white-50 mb-4">Join students, faculty and administrators of Dev Sanskriti Vishwavidyalaya using DSVV SmartCampus.</p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <?php if ($u): ?>
                <a href="<?= url(role_home($u['role'])) ?>" class="btn btn-gold btn-lg px-5"><i class="bi bi-speedometer2 me-2"></i>Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= url('/register.php') ?>" class="btn btn-gold btn-lg px-5">Get Started</a>
                <a href="<?= url('/login.php') ?>" class="btn btn-outline-white btn-lg px-5">Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
