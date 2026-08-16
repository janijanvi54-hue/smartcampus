<?php
/**
 * Public page footer + scripts.
 * Renders flash toasts.
 */
$flashes = function_exists('get_flashes') ? get_flashes() : [];
?>
</main>

<footer class="sc-footer">
    <div class="container">
        <div class="row g-4 py-5">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="sc-logo sc-logo-lg"><?= e(APP_NAME[0]) ?></span>
                    <div>
                        <div class="fw-bold sc-brand-text"><?= e(APP_NAME) ?></div>
                        <small class="text-muted"><?= e(APP_UNIVERSITY) ?></small>
                    </div>
                </div>
                <p class="text-muted">Find, book and manage the rich infrastructure of Dev Sanskriti Vishwavidyalaya &mdash; auditoriums, seminar halls, labs, hostels, canteens and health centres. Smart allocation, live utilisation analytics and intelligent recommendations.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="fw-bold text-uppercase mb-3">Platform</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= url('/index.php') ?>" class="sc-footer-link">Home</a></li>
                    <li class="mb-2"><a href="<?= url('/about.php') ?>" class="sc-footer-link">About</a></li>
                    <li class="mb-2"><a href="<?= url('/login.php') ?>" class="sc-footer-link">Login</a></li>
                    <li class="mb-2"><a href="<?= url('/register.php') ?>" class="sc-footer-link">Register</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="fw-bold text-uppercase mb-3">Resource Types</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= url('/resources.php?type=auditorium') ?>" class="sc-footer-link">Auditoriums &amp; Seminar Halls</a></li>
                    <li class="mb-2"><a href="<?= url('/resources.php?type=computer_lab') ?>" class="sc-footer-link">Classrooms &amp; Computer Labs</a></li>
                    <li class="mb-2"><a href="<?= url('/resources.php?type=library') ?>" class="sc-footer-link">Central Library &amp; Study Spaces</a></li>
                    <li class="mb-2"><a href="<?= url('/resources.php?type=hostel') ?>" class="sc-footer-link">Hostels, Canteens &amp; Health Centres</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="fw-bold text-uppercase mb-3">Contact</h6>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= e(APP_CAMPUS) ?></li>
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i><a href="mailto:info@dsvv.ac.in" class="sc-footer-link">info@dsvv.ac.in</a></li>
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i><a href="tel:+911334261367" class="sc-footer-link">+91 1334 261 367</a></li>
                    <li class="mb-2"><i class="bi bi-globe me-2"></i><a href="https://www.dsvv.ac.in" target="_blank" rel="noopener" class="sc-footer-link">www.dsvv.ac.in</a></li>
                </ul>
                <h6 class="fw-bold text-uppercase mb-3">Follow Us</h6>
                <div class="d-flex gap-2">
                    <a href="https://www.instagram.com/dsvvofficial" target="_blank" rel="noopener" class="sc-social" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.facebook.com/dsvvofficial" target="_blank" rel="noopener" class="sc-social" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.youtube.com/channel/UC5OXRsDLXxKgIk9x11AwgqQ" target="_blank" rel="noopener" class="sc-social" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="border-top py-3 d-flex flex-column flex-md-row justify-content-between gap-2">
            <small class="text-muted">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. MCA student project.</small>
            <small class="text-muted">Smart allocation &middot; Live analytics &middot; Intelligent recommendations</small>
        </div>
    </div>
</footer>

<script>window.APP_URL = <?= json_encode(url('/')) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('/assets/js/main.js') ?>"></script>
<?php if (!empty($extra_scripts)) echo $extra_scripts; ?>
<script>
<?php if (!empty($flashes)): ?>
    const flashes = <?= json_encode($flashes) ?>;
    flashes.forEach(f => scToast(f.type, f.message));
<?php endif; ?>
</script>
</body>
</html>
