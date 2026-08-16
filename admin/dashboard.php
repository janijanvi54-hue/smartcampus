<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$u = require_role('admin', '/login.php');
$pdo = db();

$page_title = 'Admin Dashboard';
$active = 'dashboard';

$totalRes   = (int)$pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
$activeRes  = (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE status = 'active'")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$todayBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE date = CURDATE()")->fetchColumn();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$openComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status != 'resolved'")->fetchColumn();

$under = under_utilized_resources($pdo);
$over  = overcrowded_resources($pdo);
$periods = demand_periods($pdo);
$busiestDay = busiest_weekday($pdo);

// Generated smart insights (from real DB data)
$insights = [];
foreach ($over as $r) {
    $insights[] = ['type' => 'danger', 'text' => "{$r['name']} is experiencing high demand - average utilisation {$r['avg_util']}% (peak {$r['peak_util']}%). Consider moving a suitable session to a less busy resource."];
}
foreach ($under as $r) {
    $insights[] = ['type' => 'info', 'text' => "{$r['name']} has an average utilisation of only {$r['avg_util']}%. Consider assigning smaller classes or study activities to this room."];
}
if ($periods['peak'] !== '-') {
    $insights[] = ['type' => 'warn', 'text' => "{$periods['peak']} is the peak campus resource period. Demand is highest in these hours."];
}
if ($periods['low'] !== '-') {
    $insights[] = ['type' => 'success', 'text' => "{$periods['low']} is a low-demand period - a good window for maintenance and re-allocation."];
}
if ($busiestDay !== '-') {
    $insights[] = ['type' => 'warn', 'text' => "{$busiestDay} has the highest campus resource demand of the week."];
}
$insights = array_slice($insights, 0, 6);

require_once __DIR__ . '/../includes/dash_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#2563eb"><i class="bi bi-building"></i></div>
            <div class="sc-stat-value"><?= (int)$totalRes ?></div>
            <div class="sc-stat-label">Total Resources <span class="text-success">(<?= (int)$activeRes ?> active)</span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#16a34a"><i class="bi bi-check-circle"></i></div>
            <div class="sc-stat-value"><?= (int)max(0, $activeRes - count($under) - count($over)) ?></div>
            <div class="sc-stat-label">Currently Occupied</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#7c3aed"><i class="bi bi-people"></i></div>
            <div class="sc-stat-value"><?= (int)$totalUsers ?></div>
            <div class="sc-stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#f59e0b"><i class="bi bi-calendar-event"></i></div>
            <div class="sc-stat-value"><?= (int)$todayBookings ?></div>
            <div class="sc-stat-label">Today's Bookings</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#dc2626"><i class="bi bi-hourglass-split"></i></div>
            <div class="sc-stat-value"><?= (int)$pending ?></div>
            <div class="sc-stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#0ea5e9"><i class="bi bi-arrow-down-right"></i></div>
            <div class="sc-stat-value"><?= count($under) ?></div>
            <div class="sc-stat-label">Under-utilized Resources</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#dc2626"><i class="bi bi-arrow-up-right"></i></div>
            <div class="sc-stat-value"><?= count($over) ?></div>
            <div class="sc-stat-label">Overcrowded Resources</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sc-stat sc-card">
            <div class="sc-stat-icon" style="background:#f59e0b"><i class="bi bi-tools"></i></div>
            <div class="sc-stat-value"><?= (int)$openComplaints ?></div>
            <div class="sc-stat-label">Open Complaints</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="sc-card mb-4">
            <div class="card-header-sc"><i class="bi bi-bulb text-warning"></i> Smart Insights <small class="text-muted fw-normal">(generated from live data)</small></div>
            <div class="p-3">
                <?php if (!$insights): ?>
                    <p class="text-muted small mb-0">No insights yet. More usage data will surface insights automatically.</p>
                <?php else: ?>
                    <?php foreach ($insights as $ins): ?>
                    <div class="insight-card <?= $ins['type'] === 'info' ? 'info' : ($ins['type'] === 'warn' ? 'warn' : ($ins['type'] === 'danger' ? 'danger' : 'success')) ?> mb-2">
                        <i class="bi bi-<?= $ins['type'] === 'danger' ? 'exclamation-triangle' : ($ins['type'] === 'warn' ? 'exclamation-circle' : ($ins['type'] === 'info' ? 'info-circle' : 'check-circle')) ?> me-1"></i>
                        <?= e($ins['text']) ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-pie-chart text-primary"></i> Booking Status Distribution</div>
            <div class="p-3"><div class="chart-box" style="height:260px"><canvas id="chartStatus"></canvas></div></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="sc-card mb-4">
            <div class="card-header-sc"><i class="bi bi-bar-chart text-primary"></i> Resource Utilization by Type</div>
            <div class="p-3"><div class="chart-box"><canvas id="chartType"></canvas></div></div>
        </div>
        <div class="sc-card mb-4">
            <div class="card-header-sc"><i class="bi bi-graph-up text-success"></i> Daily Booking Trends <small class="text-muted fw-normal">(last 14 days)</small></div>
            <div class="p-3"><div class="chart-box"><canvas id="chartDaily"></canvas></div></div>
        </div>
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-clock text-info"></i> Hourly Occupancy <small class="text-muted fw-normal">(avg users)</small></div>
            <div class="p-3"><div class="chart-box"><canvas id="chartHourly"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-easel text-primary"></i> Classroom Utilization</div>
            <div class="p-3"><div class="chart-box"><canvas id="chartClassroom"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-display text-primary"></i> Lab Utilization</div>
            <div class="p-3"><div class="chart-box"><canvas id="chartLab"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-journal-bookmark text-primary"></i> Library Occupancy</div>
            <div class="p-3"><div class="chart-box"><canvas id="chartLibrary"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sc-card">
            <div class="card-header-sc"><i class="bi bi-arrow-up-right-circle text-danger"></i> Overcrowded / Under-utilized Snapshot</div>
            <div class="p-4">
                <div class="row text-center g-3 mb-4">
                    <div class="col-6">
                        <div class="display-5 fw-bold text-danger"><?= count($over) ?></div>
                        <div class="text-muted small">Overcrowded</div>
                    </div>
                    <div class="col-6">
                        <div class="display-5 fw-bold text-info"><?= count($under) ?></div>
                        <div class="text-muted small">Under-utilized</div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <?php foreach (array_merge(array_slice($over, 0, 3), array_slice($under, 0, 3)) as $r): ?>
                    <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2">
                        <span class="small fw-semibold"><?= e($r['name']) ?></span>
                        <span class="badge text-bg-<?= isset($r['peak_util']) ? 'danger' : 'info' ?>"><?= (float)$r['avg_util'] ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '<script src="' . url('/assets/js/charts.js') . '"></script>';
require_once __DIR__ . '/../includes/dash_footer.php';
?>
