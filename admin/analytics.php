<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'Analytics, Recommendations & Reports';
$active = 'analytics';

$under = under_utilized_resources($pdo);
$over  = overcrowded_resources($pdo);
$periods = demand_periods($pdo);
$busiestDay = busiest_weekday($pdo);
$byType = utilization_by_type($pdo);
$comparison = utilization_by_resource($pdo);

$reports = [
    ['key' => 'resources',      'label' => 'Resource Utilization Report',  'icon' => 'bar-chart',     'desc' => 'Utilisation and occupancy for every resource.'],
    ['key' => 'bookings',       'label' => 'Booking Report',               'icon' => 'calendar-check','desc' => 'All bookings with user, resource and status.'],
    ['key' => 'overcrowding',   'label' => 'Overcrowding Report',          'icon' => 'arrow-up-right','desc' => 'Resources operating above healthy utilisation.'],
    ['key' => 'underutilized',  'label' => 'Under-utilization Report',     'icon' => 'arrow-down-right','desc' => 'Resources running below the utilisation target.'],
    ['key' => 'complaints',     'label' => 'Complaint Report',             'icon' => 'wrench-adjustable','desc' => 'All reported facility problems and their status.'],
    ['key' => 'users',          'label' => 'User Activity Report',         'icon' => 'people',        'desc' => 'Users, roles and booking activity.'],
];

require_once __DIR__ . '/../includes/dash_header.php';
?>

<ul class="nav sc-tabs gap-2 mb-4">
    <li class="nav-item"><a class="nav-link active" href="#" data-sc-tab="under"><i class="bi bi-arrow-down-right me-1"></i>Under-utilized</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-sc-tab="over"><i class="bi bi-arrow-up-right me-1"></i>Overcrowded</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-sc-tab="compare"><i class="bi bi-compare me-1"></i>Type Comparison</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-sc-tab="demand"><i class="bi bi-clock-history me-1"></i>Demand Periods</a></li>
    <li class="nav-item"><a class="nav-link" href="#" data-sc-tab="reports"><i class="bi bi-file-earmark-arrow-down me-1"></i>Reports &amp; Export</a></li>
</ul>

<!-- Under-utilized -->
<div id="sc-panel-under" class="sc-panel">
    <div class="row g-3">
        <div class="col-12">
            <div class="sc-card p-4 mb-3 sc-banner">
                <h5 class="fw-bold mb-1"><i class="bi bi-arrow-down-right me-2"></i>Under-utilized Resources</h5>
                <p class="opacity-75 mb-0">Resources running below the configured utilisation target (<strong><?= (int)utilization_thresholds()['under'] ?>%</strong>). These rooms can absorb more activity.</p>
            </div>
        </div>
        <?php if (!$under): ?>
            <div class="col-12 text-center text-muted py-5">No under-utilized resources detected.</div>
        <?php else: ?>
            <?php foreach ($under as $r): ?>
            <div class="col-md-6 col-xl-4">
                <div class="sc-card h-100 p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <h6 class="fw-bold mb-0"><?= e($r['name']) ?></h6>
                            <small class="text-muted"><?= e(resource_type_label($r['type'])) ?> &middot; Capacity <?= (int)$r['capacity'] ?></small>
                        </div>
                        <span class="rec-badge" style="background:#e0f2fe;color:#0369a1"><?= (float)$r['avg_util'] ?>%</span>
                    </div>
                    <div class="util-bar mb-3"><span style="width:<?= (float)$r['avg_util'] ?>%;background:#0ea5e9"></span></div>
                    <div class="small text-muted mb-2">Average users: <?= (int)$r['avg_users'] ?> / <?= (int)$r['capacity'] ?></div>
                    <div class="alert alert-info py-2 mb-0 small"><i class="bi bi-lightbulb me-1"></i>Consider assigning smaller classes or study activities to this room.</div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Overcrowded -->
<div id="sc-panel-over" class="sc-panel d-none">
    <div class="row g-3">
        <div class="col-12">
            <div class="sc-card p-4 mb-3 sc-banner-red">
                <h5 class="fw-bold mb-1"><i class="bi bi-arrow-up-right me-2"></i>Overcrowded Resources</h5>
                <p class="opacity-75 mb-0">Resources at or above capacity (<strong><?= (int)utilization_thresholds()['over'] ?>%</strong>). Sessions should be redistributed to lower-demand rooms.</p>
            </div>
        </div>
        <?php if (!$over): ?>
            <div class="col-12 text-center text-muted py-5">No overcrowded resources detected.</div>
        <?php else: ?>
            <?php foreach ($over as $r):
                // find a good alternative of the same type
                $alt = $pdo->prepare("SELECT name, capacity FROM resources WHERE type = :t AND status='active' AND id <> :id2 ORDER BY id");
                $alt->execute([':t' => $r['type'], ':id2' => (int)$r['id']]);
                $alts = $alt->fetchAll();
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="sc-card h-100 p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <h6 class="fw-bold mb-0"><?= e($r['name']) ?></h6>
                            <small class="text-muted"><?= e(resource_type_label($r['type'])) ?> &middot; Capacity <?= (int)$r['capacity'] ?></small>
                        </div>
                        <span class="rec-badge" style="background:#fee2e2;color:#991b1b"><?= (float)$r['avg_util'] ?>% avg</span>
                    </div>
                    <div class="small text-muted mb-2">Peak utilisation: <strong class="text-danger"><?= (float)$r['peak_util'] ?>%</strong> &middot; Avg users: <?= (int)$r['avg_users'] ?></div>
                    <div class="alert alert-danger py-2 mb-2 small"><i class="bi bi-exclamation-triangle me-1"></i>Consider moving a suitable session to a less busy room.</div>
                    <?php if ($alts): ?>
                        <small class="text-muted">Possible alternatives:</small>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php foreach (array_slice($alts, 0, 3) as $a): ?>
                                <span class="badge text-bg-light border"><?= e($a['name']) ?> (<?= (int)$a['capacity'] ?>)</span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Type comparison -->
<div id="sc-panel-compare" class="sc-panel d-none">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="sc-card">
                <div class="card-header-sc"><i class="bi bi-compare text-primary"></i> Resource Type Comparison</div>
                <div class="p-3"><div class="chart-box"><canvas id="chartCompare"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="sc-card">
                <div class="card-header-sc"><i class="bi bi-list-ul text-primary"></i> Per-Resource Utilisation</div>
                <div class="p-3" style="max-height:420px;overflow:auto">
                    <?php foreach ($comparison as $c):
                        $cls = classify_utilization((float)$c['avg_util']); ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold"><?= e($c['name']) ?></span>
                                <span><span class="sc-dot dot-<?= $cls['dot'] ?>"></span><?= (float)$c['avg_util'] ?>%</span>
                            </div>
                            <div class="util-bar"><span style="width:<?= min(100, (float)$c['avg_util']) ?>%;background:<?= match ($cls['color']) {
                                'success' => '#16a34a', 'warning' => '#f59e0b', 'danger' => '#dc2626', 'info' => '#0ea5e9'
                            } ?>"></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demand periods -->
<div id="sc-panel-demand" class="sc-panel d-none">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="sc-card p-4 h-100">
                <h6 class="fw-bold"><i class="bi bi-arrow-up-short text-danger me-2"></i>Peak Usage Periods</h6>
                <div class="display-5 fw-bold text-danger my-3"><?= e($periods['peak']) ?></div>
                <p class="text-muted small mb-0">Highest demand hours across the campus. Schedule high-occupancy sessions here, but be ready for congestion in popular labs and classrooms.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="sc-card p-4 h-100">
                <h6 class="fw-bold"><i class="bi bi-arrow-down-short text-success me-2"></i>Low-Demand Periods</h6>
                <div class="display-5 fw-bold text-success my-3"><?= e($periods['low']) ?></div>
                <p class="text-muted small mb-0">Ideal windows for maintenance, equipment upgrades and moving sessions to relieve overcrowding.</p>
            </div>
        </div>
        <div class="col-12">
            <div class="sc-card p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-calendar-week text-primary me-2"></i>Weekly Demand Pattern</h6>
                <p class="text-muted mb-3"><?= e($busiestDay) ?> has the highest campus resource demand of the week.</p>
                <div class="chart-box" style="height:280px"><canvas id="chartDemand"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Reports -->
<div id="sc-panel-reports" class="sc-panel d-none">
    <div class="row g-3">
        <div class="col-12">
            <div class="sc-card p-4 sc-banner">
                <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-arrow-down me-2"></i>Reports &amp; CSV Export</h5>
                <p class="opacity-75 mb-0">Download filtered reports generated from live database data.</p>
            </div>
        </div>
        <?php foreach ($reports as $rpt): ?>
        <div class="col-md-6 col-xl-4">
            <div class="sc-card h-100 p-4 d-flex flex-column">
                <div class="sc-cat-icon mb-3" style="background:#2563eb"><i class="bi bi-<?= $rpt['icon'] ?>"></i></div>
                <h6 class="fw-bold"><?= e($rpt['label']) ?></h6>
                <p class="text-muted small flex-grow-1"><?= e($rpt['desc']) ?></p>
                <a class="btn btn-outline-primary btn-sm" href="<?= url('/api/export.php?report=' . $rpt['key']) ?>">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/charts.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
