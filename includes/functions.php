<?php
/**
 * SmartCampus - shared helper functions
 * Includes: escaping, redirects, flash messages, notifications,
 * availability checks, utilization calculations and the
 * Smart Resource Recommendation Engine.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------ */
/* Output / misc helpers                                               */
/* ------------------------------------------------------------------ */

/** Escape output for safe HTML rendering. */
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Absolute URL for an app path (e.g. url('/login.php')). */
function url(string $path = '/'): string {
    if ($path === '/') return APP_URL !== '' ? APP_URL . '/' : '/';
    return APP_URL . $path;
}

/** Redirect to a URL (relative to app root). */
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

/** Human-friendly date. */
function fmt_date(?string $date): string {
    if (!$date) return '-';
    $d = new DateTime($date);
    return $d->format(DATE_FORMAT);
}

/** Human-friendly time. */
function fmt_time(?string $time): string {
    if ($time === null || $time === '') return '-';
    $t = new DateTime($time);
    return $t->format(TIME_FORMAT);
}

/** Set a one-shot flash message. */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Get and clear flash messages. */
function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Render JSON response for API endpoints. */
function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/** JSON request body parser (for fetch() POST payloads). */
function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* ------------------------------------------------------------------ */
/* Settings                                                            */
/* ------------------------------------------------------------------ */

/** Read a settings value with fallback. */
function get_setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = [];
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/** Thresholds currently in effect. */
function utilization_thresholds(): array {
    return [
        'under' => (int)get_setting('under_utilized_threshold', THRESHOLD_UNDER_UTILIZED),
        'normal' => (int)get_setting('normal_threshold', THRESHOLD_NORMAL),
        'over' => (int)get_setting('overcrowded_threshold', THRESHOLD_OVERCROWDED),
        'auto_approve' => (int)get_setting('auto_approve_bookings', 0) === 1,
    ];
}

/* ------------------------------------------------------------------ */
/* Resource availability, utilization and classification              */
/* ------------------------------------------------------------------ */

const RESOURCE_TYPES = [
    'auditorium'    => 'Auditorium',
    'seminar_hall'  => 'Seminar Hall',
    'classroom'     => 'Classroom',
    'computer_lab'  => 'Computer Lab',
    'library'       => 'Library',
    'study_room'    => 'Study Room',
    'meeting_room'  => 'Meeting Room',
    'hostel'        => 'Hostel',
    'canteen'       => 'Canteen',
    'health_centre' => 'Health Centre',
    'guest_house'   => 'Guest House',
    'amenity'       => 'Amenity',
];

const BOOKING_STATUSES = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];

/** Type label helper. */
function resource_type_label(?string $type): string {
    return RESOURCE_TYPES[$type] ?? ($type ?? '-');
}

/**
 * Is the resource free during a requested slot?
 * Overlaps with any pending/approved booking are treated as conflicts.
 */
function is_resource_available(PDO $pdo, int $resourceId, string $date, string $start, string $end, int $excludeBookingId = 0): bool {
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE resource_id = :rid AND date = :date
              AND status IN ('pending','approved')
              AND id <> :exclude
              AND start_time < :end AND end_time > :start";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':rid'     => $resourceId,
        ':date'    => $date,
        ':start'   => $start,
        ':end'     => $end,
        ':exclude' => $excludeBookingId,
    ]);
    return (int)$stmt->fetchColumn() === 0;
}

/**
 * Average historical utilization + average occupancy for a resource
 * based on usage_records (recent data).
 */
function resource_utilization(PDO $pdo, int $resourceId, int $lookbackDays = 30): array {
    $sql = "SELECT AVG(utilization_percentage) AS avg_util,
                   AVG(users_count) AS avg_users,
                   COUNT(*) AS samples,
                   MAX(date) AS last_used
            FROM usage_records
            WHERE resource_id = :rid AND date >= (CURDATE() - INTERVAL :lb DAY)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rid' => $resourceId, ':lb' => $lookbackDays]);
    $row = $stmt->fetch();
    $avg = (float)($row['avg_util'] ?? 0);
    return [
        'avg_utilization' => round($avg, 1),
        'avg_users'       => (int)round((float)($row['avg_users'] ?? 0)),
        'samples'         => (int)($row['samples'] ?? 0),
        'last_used'       => $row['last_used'] ?? null,
    ];
}

/**
 * Classify a utilization percentage into a status bucket.
 * Returns key + label + css color class.
 */
function classify_utilization(float $util): array {
    $t = utilization_thresholds();
    if ($util > $t['over']) {
        return ['key' => 'overcrowded', 'label' => 'Overcrowded', 'color' => 'danger', 'dot' => 'danger'];
    }
    if ($util > $t['normal']) {
        return ['key' => 'high', 'label' => 'High Utilization', 'color' => 'warning', 'dot' => 'warning'];
    }
    if ($util >= $t['under']) {
        return ['key' => 'normal', 'label' => 'Normal', 'color' => 'success', 'dot' => 'success'];
    }
    return ['key' => 'under', 'label' => 'Under-utilized', 'color' => 'info', 'dot' => 'info'];
}

/** Full resource record with live utilization + status computed. */
function resource_with_utilization(PDO $pdo, array $resource): array {
    $u = resource_utilization($pdo, (int)$resource['id']);
    $cls = classify_utilization($u['avg_utilization']);
    $resource['avg_utilization'] = $u['avg_utilization'];
    $resource['avg_users']       = $u['avg_users'];
    $resource['utilization_class'] = $cls;
    return $resource;
}

/** All active resources decorated with utilization. */
function all_resources(PDO $pdo): array {
    $rows = $pdo->query("SELECT * FROM resources WHERE status = 'active' ORDER BY type, name")->fetchAll();
    return array_map(fn($r) => resource_with_utilization($pdo, $r), $rows);
}

/**
 * Can a user with the given role book a resource whose bookable_by is set?
 * - admin  : everything
 * - student: 'all' and 'student'
 * - faculty: 'all' and 'faculty'
 */
function can_role_book(string $role, ?string $bookableBy): bool {
    if ($role === 'admin') return true;
    if ($bookableBy === 'all' || $bookableBy === null) return true;
    return $bookableBy === $role;
}

/**
 * Where should a user land after login?
 * Supports a validated internal $return path; resource-detail returns are
 * routed to the caller's role-appropriate booking page.
 */
function resolve_post_login_redirect(array $u, string $return): string {
    if ($return !== '') {
        if (preg_match('#resource-details\.php\?id=(\d+)#', $return, $m)) {
            $id = (int)$m[1];
            return match ($u['role']) {
                'admin'   => '/admin/resources.php',
                'faculty' => '/faculty/book-resource.php?resource_id=' . $id,
                default   => '/student/resource-details.php?id=' . $id,
            };
        }
        if (str_starts_with($return, '/') && !str_contains($return, '://')) {
            $expected = '/' . $u['role'] . '/';
            if (str_starts_with($return, $expected)) return $return;
        }
    }
    return role_home($u['role']);
}

/* ------------------------------------------------------------------ */
/* Notifications                                                       */
/* ------------------------------------------------------------------ */

/** Create a notification for a user. */
function send_notification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info'): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:u, :t, :m, :ty)");
    $stmt->execute([':u' => $userId, ':t' => $title, ':m' => $message, ':ty' => $type]);
}

/** Count unread notifications for a user. */
function unread_notifications(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0");
    $stmt->execute([':u' => $userId]);
    return (int)$stmt->fetchColumn();
}

/* ------------------------------------------------------------------ */
/* Booking helpers                                                     */
/* ------------------------------------------------------------------ */

/** Booking status badge html class. */
function booking_status_class(string $status): string {
    return match ($status) {
        'approved'  => 'success',
        'pending'   => 'warning',
        'rejected'  => 'danger',
        'cancelled' => 'secondary',
        'completed' => 'info',
        default     => 'secondary',
    };
}

/** Validate a booking request; returns [true, []] or [false, [errors]]. */
function validate_booking(PDO $pdo, int $resourceId, string $date, string $start, string $end, int $expectedUsers): array {
    $errors = [];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $errors[] = 'Please provide a valid booking date.';
    }
    // Accept HH:MM or HH:MM:SS and normalise to HH:MM
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start)) {
        $errors[] = 'Please provide a valid start time.';
    } elseif (strlen($start) > 5) {
        $start = substr($start, 0, 5);
    }
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $end)) {
        $errors[] = 'Please provide a valid end time.';
    } elseif (strlen($end) > 5) {
        $end = substr($end, 0, 5);
    }
    if ($errors) return [false, $errors];

    if ($date < date('Y-m-d')) {
        $errors[] = 'You cannot book a resource in the past.';
    }
    if ($start >= $end) {
        $errors[] = 'End time must be after start time.';
    }
    if ($date === date('Y-m-d') && $start <= date('H:i')) {
        $errors[] = 'Start time must be in the future for today\'s bookings.';
    }
    if ($expectedUsers <= 0) {
        $errors[] = 'Number of expected users must be at least 1.';
    }

    $stmt = $pdo->prepare("SELECT capacity FROM resources WHERE id = :id");
    $stmt->execute([':id' => $resourceId]);
    $capacity = (int)$stmt->fetchColumn();
    if ($capacity <= 0) {
        $errors[] = 'This resource is not available for booking.';
    }
    if ($expectedUsers > $capacity) {
        $errors[] = "This resource has a capacity of {$capacity}. Your request for {$expectedUsers} users exceeds the capacity.";
    }
    if (!is_resource_available($pdo, $resourceId, $date, $start, $end)) {
        $errors[] = 'This resource is already booked during the requested time slot.';
    }

    return $errors ? [false, $errors] : [true, []];
}

/** Create a booking (after validation) and optionally notify the requesting user. */
function create_booking(PDO $pdo, int $userId, int $resourceId, string $date, string $start, string $end, int $expectedUsers, string $purpose): int {
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, resource_id, date, start_time, end_time, expected_users, purpose, status)
                           VALUES (:u, :r, :d, :s, :e, :x, :p, 'pending')");
    $stmt->execute([
        ':u' => $userId, ':r' => $resourceId, ':d' => $date,
        ':s' => $start, ':e' => $end, ':x' => $expectedUsers, ':p' => $purpose,
    ]);
    return (int)$pdo->lastInsertId();
}

/** Expected utilization (as %) of a request against a resource. */
function expected_utilization(int $expectedUsers, int $capacity): float {
    if ($capacity <= 0) return 0;
    return round($expectedUsers / $capacity * 100, 1);
}

/* ------------------------------------------------------------------ */
/* SMART RESOURCE RECOMMENDATION ENGINE                               */
/* ------------------------------------------------------------------ */

/**
 * Find and rank alternative resources for a request.
 *
 * Input:  requested resource id (may be unavailable), date, start, end,
 *         expected users, optional preferred location keyword.
 * Output: ranked list of suitable alternatives + reasons + score.
 */
function recommend_resources(PDO $pdo, array $request): array {
    $type        = $request['type'] ?? null;
    $date        = $request['date'];
    $start       = $request['start'];
    $end         = $request['end'];
    $users       = (int)$request['expected_users'];
    $excludeId   = (int)($request['requested_resource_id'] ?? 0);
    $location    = strtolower(trim($request['location'] ?? ''));
    $maxResults  = (int)($request['max_results'] ?? 5);
    $role        = $request['role'] ?? null;

    if ($users <= 0) return [];

    // Candidate resources of the requested type, active, with enough capacity,
    // and only those the caller's role may book.
    $sql = "SELECT * FROM resources WHERE status = 'active' AND capacity >= :users";
    $params = [':users' => $users];
    if ($role && $role !== 'admin') {
        $sql .= " AND bookable_by IN ('all', :role)";
        $params[':role'] = $role;
    }
    if ($type) {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }
    $sql .= " ORDER BY type, name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll();

    $results = [];
    foreach ($candidates as $res) {
        $resId = (int)$res['id'];
        if ($resId === $excludeId) continue;

        // Hard filters: must be free during the slot.
        if (!is_resource_available($pdo, $resId, $date, $start, $end)) {
            continue;
        }

        $util   = resource_utilization($pdo, $resId, 30);
        $avgU   = $util['avg_utilization'];
        $expU   = expected_utilization($users, (int)$res['capacity']);

        // Score: higher is better.
        //  - capacity fit: ideal to have ~1.1-1.3x headroom; penalise huge rooms slightly
        //  - prefer lower current utilization
        //  - expected utilization near 70-90% is ideal
        $score = 0.0;
        $capacityFit = (int)$res['capacity'] >= $users ? 1.0 : 0.0;
        $headroom = (int)$res['capacity'] / max(1, $users);
        if ($headroom < 1.3)      $score += 30;
        elseif ($headroom < 2.0)  $score += 22;
        elseif ($headroom < 3.0)  $score += 14;
        else                      $score += 6;   // oversized room

        $locationMatch = $location !== '' && stripos((string)$res['location'], $location) !== false;
        $score += (100 - $avgU) * 0.35;            // lower current utilisation is better
        $score += (100 - abs($expU - 80)) * 0.20;  // near-ideal expected utilisation
        if ($locationMatch) {
            $score += 8;                            // location bonus
        }
        $score = round(min(100, max(0, $score)), 1);

        // Human-readable reasons.
        $reasons = [];
        $reasons[] = "Sufficient capacity ({$res['capacity']}) for {$users} users";
        $reasons[] = "Available during the requested time slot";
        if ($avgU <= 30)      $reasons[] = "Current utilisation is low ({$avgU}%)";
        elseif ($avgU <= 70)  $reasons[] = "Current utilisation is moderate ({$avgU}%)";
        else                  $reasons[] = "Current utilisation is high ({$avgU}%)";
        if ($expU > 100)      $reasons[] = "Expected utilisation would be {$expU}% (overcrowded)";
        else                  $reasons[] = "Expected utilisation {$expU}% stays within capacity";
        if ($locationMatch) {
            $reasons[] = 'Matches your preferred location';
        }
        $reasons[] = 'No booking conflict';

        $results[] = [
            'resource'            => $res,
            'avg_utilization'     => $avgU,
            'expected_utilization'=> $expU,
            'available'           => true,
            'score'               => $score,
            'reasons'             => $reasons,
        ];
    }

    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, $maxResults);
}

/** Persist a recommendation proposal. */
function store_recommendation(PDO $pdo, ?int $bookingId, int $requestedId, int $recommendedId, array $reasons, float $score, string $status = 'proposed'): int {
    $stmt = $pdo->prepare("INSERT INTO recommendations (booking_id, requested_resource_id, recommended_resource_id, reason, score, status)
                           VALUES (:b, :req, :rec, :why, :s, :st)");
    $stmt->execute([
        ':b' => $bookingId, ':req' => $requestedId, ':rec' => $recommendedId,
        ':why' => implode('. ', $reasons), ':s' => $score, ':st' => $status,
    ]);
    return (int)$pdo->lastInsertId();
}

/* ------------------------------------------------------------------ */
/* Analytics helpers                                                   */
/* ------------------------------------------------------------------ */

/** Average utilization grouped by resource type. */
function utilization_by_type(PDO $pdo): array {
    return $pdo->query(
        "SELECT r.type, ROUND(AVG(u.utilization_percentage),1) AS avg_util
         FROM usage_records u
         JOIN resources r ON r.id = u.resource_id
         WHERE u.date >= (CURDATE() - INTERVAL 30 DAY)
         GROUP BY r.type ORDER BY avg_util DESC"
    )->fetchAll();
}

/** Daily booking count over the last n days. */
function daily_booking_trend(PDO $pdo, int $days = 14): array {
    $rows = $pdo->query(
        "SELECT date, COUNT(*) AS total,
                SUM(status='pending') AS pending,
                SUM(status='approved') AS approved
         FROM bookings
         WHERE date >= (CURDATE() - INTERVAL " . (int)$days . " DAY)
         GROUP BY date ORDER BY date"
    )->fetchAll();
    $all = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("today -{$i} days"));
        $row = array_filter($rows, fn($r) => $r['date'] === $d);
        $row = $row ? array_values($row)[0] : null;
        $all[] = [
            'date'     => $d,
            'total'    => (int)($row['total'] ?? 0),
            'pending'  => (int)($row['pending'] ?? 0),
            'approved' => (int)($row['approved'] ?? 0),
        ];
    }
    return $all;
}

/** Average occupancy per hour of day (from usage_records). */
function hourly_occupancy(PDO $pdo): array {
    $rows = $pdo->query(
        "SELECT HOUR(start_time) AS hour, ROUND(AVG(users_count),1) AS avg_users,
                ROUND(AVG(utilization_percentage),1) AS avg_util
         FROM usage_records
         WHERE date >= (CURDATE() - INTERVAL 30 DAY)
         GROUP BY HOUR(start_time) ORDER BY hour"
    )->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[(int)$r['hour']] = $r;
    $result = [];
    for ($h = 8; $h <= 17; $h++) {
        $result[] = [
            'hour' => sprintf('%02d:00', $h),
            'avg_users' => (float)($map[$h]['avg_users'] ?? 0),
            'avg_util'  => (float)($map[$h]['avg_util'] ?? 0),
        ];
    }
    return $result;
}

/** Per-resource average utilization (optionally filtered by type). */
function utilization_by_resource(PDO $pdo, ?string $type = null): array {
    $sql = "SELECT r.id, r.name, r.type, r.capacity, r.location, ROUND(AVG(u.utilization_percentage),1) AS avg_util,
                   ROUND(AVG(u.users_count),0) AS avg_users
            FROM usage_records u
            JOIN resources r ON r.id = u.resource_id
            WHERE u.date >= (CURDATE() - INTERVAL 30 DAY)";
    $params = [];
    if ($type) { $sql .= " AND r.type = :type"; $params[':type'] = $type; }
    $sql .= " GROUP BY r.id ORDER BY avg_util DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Booking status distribution. */
function booking_status_distribution(PDO $pdo): array {
    return $pdo->query(
        "SELECT status, COUNT(*) AS total FROM bookings GROUP BY status"
    )->fetchAll();
}

/** Under-utilized resources (avg < threshold). */
function under_utilized_resources(PDO $pdo, int $lookbackDays = 30): array {
    $thr = utilization_thresholds()['under'];
    $sql = "SELECT r.*, ROUND(AVG(u.utilization_percentage),1) AS avg_util,
                   ROUND(AVG(u.users_count),0) AS avg_users
            FROM resources r
            JOIN usage_records u ON u.resource_id = r.id
            WHERE u.date >= (CURDATE() - INTERVAL :lb DAY) AND r.status = 'active'
            GROUP BY r.id
            HAVING avg_util <= :thr
            ORDER BY avg_util ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lb' => $lookbackDays, ':thr' => $thr]);
    return $stmt->fetchAll();
}

/** Overcrowded resources (avg utilization exceeds the overcrowded threshold). */
function overcrowded_resources(PDO $pdo, int $lookbackDays = 30): array {
    $thr = utilization_thresholds()['over'];
    $sql = "SELECT r.*, ROUND(AVG(u.utilization_percentage),1) AS avg_util,
                   ROUND(MAX(u.utilization_percentage),1) AS peak_util,
                   ROUND(AVG(u.users_count),0) AS avg_users
            FROM resources r
            JOIN usage_records u ON u.resource_id = r.id
            WHERE u.date >= (CURDATE() - INTERVAL :lb DAY) AND r.status = 'active'
            GROUP BY r.id
            HAVING avg_util > :thr
            ORDER BY avg_util DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lb' => $lookbackDays, ':thr' => $thr]);
    return $stmt->fetchAll();
}

/** Peak / low demand hour ranges from usage data. */
function demand_periods(PDO $pdo): array {
    $rows = $pdo->query(
        "SELECT HOUR(start_time) AS hour, AVG(utilization_percentage) AS avg_util
         FROM usage_records
         WHERE date >= (CURDATE() - INTERVAL 30 DAY)
         GROUP BY HOUR(start_time)"
    )->fetchAll();
    $avg = array_column($rows, 'avg_util', 'hour');
    $hours = [];
    for ($h = 8; $h <= 17; $h++) {
        $hours[$h] = (float)($avg[$h] ?? 0);
    }
    arsort($hours);
    $top = array_slice(array_keys($hours), 0, 2);
    $bottom = array_slice(array_keys($hours), -2);
    sort($top);
    sort($bottom);
    $fmt = fn($h) => sprintf('%02d:00', $h);
    return [
        'peak'  => count($top) ? $fmt($top[0]) . ' - ' . $fmt($top[count($top)-1] + 1) : '-',
        'low'   => count($bottom) ? $fmt($bottom[0]) . ' - ' . $fmt($bottom[count($bottom)-1] + 1) : '-',
        'peak_hours' => array_map($fmt, $top),
        'low_hours'  => array_map($fmt, $bottom),
    ];
}

/** Busiest weekday (highest avg usage). */
function busiest_weekday(PDO $pdo): string {
    $rows = $pdo->query(
        "SELECT DAYOFWEEK(date) AS dow, AVG(utilization_percentage) AS avg_util
         FROM usage_records
         WHERE date >= (CURDATE() - INTERVAL 60 DAY)
         GROUP BY DAYOFWEEK(date)"
    )->fetchAll();
    $names = ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $best = null;
    foreach ($rows as $r) {
        if ($best === null || $r['avg_util'] > $best['avg_util']) $best = $r;
    }
    return $best ? $names[(int)$best['dow']] : '-';
}
