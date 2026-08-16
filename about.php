<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'About DSVV SmartCampus';
$active = 'about';

$pdo = db();
$resCounts = [];
foreach ($pdo->query("SELECT type, COUNT(*) c FROM resources GROUP BY type") as $r) $resCounts[$r['type']] = (int)$r['c'];

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-5 sc-hero">
    <div class="container py-4 text-center">
        <h1 class="fw-bold">About <span class="hero-accent">DSVV SmartCampus</span></h1>
        <p class="lead mx-auto text-white-50" style="max-width:720px">A campus-wide resource and facility management system for Dev Sanskriti Vishwavidyalaya, Shantikunj, Haridwar &mdash; making it effortless to find, book and manage the university's rich infrastructure.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold">What is DSVV SmartCampus?</h2>
                <p class="text-muted">Dev Sanskriti Vishwavidyalaya is spread over a unique campus at Shantikunj, Haridwar with state-of-the-art auditoriums, seminar halls, computer labs, hostels, cafeterias, health centres and spiritual amenities. DSVV SmartCampus connects students, faculty and administration to all of it in real time.</p>
                <p class="text-muted">It replaces manual registers and guesswork with a system that tracks availability, measures utilisation, prevents booking conflicts and automatically recommends the best alternative when your first choice is unavailable or overcrowded.</p>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>6 auditoriums &amp; 3 seminar halls, bookable online</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Role-based access for students, faculty &amp; admins</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Live utilisation analytics with Chart.js</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Smart recommendation algorithm</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Complaint tracking &amp; announcements</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="sc-card p-4">
                    <h6 class="fw-bold mb-3">The problems DSVV SmartCampus solves</h6>
                    <div class="d-flex gap-3 mb-3">
                        <div class="sc-cat-icon" style="background:#dc2626;width:40px;height:40px;font-size:1.1rem"><i class="bi bi-x-circle"></i></div>
                        <div>
                            <div class="fw-semibold">Can't find available resources</div>
                            <small class="text-muted">Search and filter everything by type, time, capacity and location.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <div class="sc-cat-icon" style="background:#f59e0b;width:40px;height:40px;font-size:1.1rem"><i class="bi bi-exclamation-triangle"></i></div>
                        <div>
                            <div class="fw-semibold">Overcrowded &amp; under-utilised facilities</div>
                            <small class="text-muted">Utilisation analysis highlights imbalance and recommends re-allocation.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <div class="sc-cat-icon" style="background:#7c3aed;width:40px;height:40px;font-size:1.1rem"><i class="bi bi-calendar-x"></i></div>
                        <div>
                            <div class="fw-semibold">Booking conflicts</div>
                            <small class="text-muted">Server-side validation blocks overlapping bookings before they happen.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="sc-cat-icon" style="background:#2563eb;width:40px;height:40px;font-size:1.1rem"><i class="bi bi-lightbulb"></i></div>
                        <div>
                            <div class="fw-semibold">No guidance when rooms are full</div>
                            <small class="text-muted">The Smart Recommendation Engine suggests the best alternative with reasons.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Campus infrastructure -->
<section class="py-5" style="background:#fff">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Campus Infrastructure at a Glance</h2>
            <p class="text-muted mx-auto" style="max-width:640px">Every resource from the DSVV campus directory is available in the system.</p>
        </div>
        <div class="row g-4">
            <?php
            $blocks = [
                ['Auditoriums', 'easel', '6 modern auditoriums including a 1,300-capacity air-cooled auditorium with PA system, audio-visual system and LCD projector with cyclorama.', 'text-purple', '#7c3aed'],
                ['Seminar Halls', 'megaphone', '3 fully-equipped seminar halls with modern ICT facilities for lectures and workshops.', 'text-warning', '#f59e0b'],
                ['Hostels', 'house-door', 'Boys (Panini, Arvind, International), Girls (Sanghmitra, Nivedita, International), Working Women Hostel and faculty residences with dining halls, Wi-Fi, laundry and more.', 'text-primary', '#2563eb'],
                ['Cafeterias', 'cup-hot', 'Annapurna, Anandmayee and Jagdamba Bhojanalaya with separate dining areas for faculty.', 'text-danger', '#ea580c'],
                ['Health Centres', 'heart-pulse', 'Triage &amp; Assessment Centre, OPD, Physiotherapy, Yoga Arogya Polyclinic, Psychological Clinic, Naturopathy, Panchakarma and a 50-bed multi-speciality hospital.', 'text-danger', '#dc2626'],
                ['Computer Labs & Library', 'display', 'Commercial Computer Lab, advanced computer labs and the Central Library with reading halls and study rooms.', 'text-success', '#16a34a'],
            ];
            foreach ($blocks as [$title, $icon, $text, $tcolor, $bg]): ?>
            <div class="col-md-6 col-lg-4">
                <div class="sc-card p-4 h-100">
                    <div class="sc-cat-icon mb-3" style="background:<?= $bg ?>"><i class="bi bi-<?= $icon ?>"></i></div>
                    <h6 class="fw-bold"><?= $title ?></h6>
                    <p class="text-muted small mb-0"><?= $text ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Recommendation concept (spec example) -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">The Smart Recommendation Concept</h2>
            <p class="text-muted mx-auto" style="max-width:640px">When a requested resource is unavailable or overcrowded, the system doesn't just say "no". It finds the best alternative and explains why.</p>
        </div>
        <div class="sc-card p-4">
            <div class="row g-4">
                <div class="col-lg-5 border-end-lg pe-lg-4">
                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-x-octagon me-2"></i>Requested</h6>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Resource</span><strong>Commercial Computer Lab</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Time</span><strong>10:00 - 11:00 AM</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Students</span><strong>45</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Capacity</span><strong>60</strong></div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Utilisation</span>
                        <span class="badge text-bg-danger"><i class="bi bi-arrow-up-right me-1"></i>Overcrowded</span>
                    </div>
                </div>
                <div class="col-lg-7">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-check-circle me-2"></i>Recommended</h6>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span class="text-muted">Resource</span>
                        <strong class="text-success"><i class="bi bi-arrow-right-circle me-1"></i>Computer Lab 3</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Capacity</span><strong>50</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Current utilisation</span><strong>38%</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Available at requested time</span><span class="badge text-bg-success">Yes</span></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Booking conflict</span><span class="badge text-bg-success">None</span></div>
                    <div class="alert alert-success mt-3 mb-0 small">
                        <i class="bi bi-lightbulb me-2"></i>Computer Lab 3 is recommended because it has sufficient capacity, is available during the requested time, and has lower current utilisation than other suitable resources.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
