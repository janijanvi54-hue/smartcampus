<?php
$flashes = function_exists('get_flashes') ? get_flashes() : [];
?>
        </main>
    </div>
</div>

<!-- Mobile sidebar -->
<div class="offcanvas offcanvas-start sc-sidebar-mobile" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header sc-sidebar-brand">
        <div class="d-flex align-items-center gap-2">
            <span class="sc-logo"><?= e(APP_NAME[0]) ?></span>
            <span class="fw-bold sc-brand-text text-white"><?= e(APP_NAME) ?></span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0"></div>
</div>

<script>window.APP_URL = <?= json_encode(url('/')) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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
