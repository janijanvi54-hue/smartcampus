<?php
/**
 * SmartCampus - global configuration
 * Adjust these values to match your local environment (defaults suit XAMPP).
 */

// --- Database credentials -------------------------------------------------
// Values come from environment variables (Render / shared hosting) and fall
// back to the local XAMPP defaults when the variables are not set.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'smartcampus');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// --- Application ----------------------------------------------------------
define('APP_NAME', 'DSVV SmartCampus');
define('APP_TAGLINE', 'Dev Sanskriti Vishwavidyalaya – Campus Resource & Facility Management System');
define('APP_UNIVERSITY', 'Dev Sanskriti Vishwavidyalaya');
define('APP_CAMPUS', 'Shantikunj, Haridwar, Uttarakhand');
define('TIMEZONE', 'Asia/Kolkata');

// Compute the app's URL path (works whether the project is at the web root,
// inside htdocs/smartcampus, or under a sub-folder). Override with APP_URL.
$envAppUrl = getenv('APP_URL');
if ($envAppUrl !== false && $envAppUrl !== '') {
    $__appUrl = rtrim($envAppUrl, '/');
} else {
    $__docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $__appRoot = realpath(__DIR__ . '/..') ?: '';
    if ($__docRoot !== '' && strpos($__appRoot, $__docRoot) === 0) {
        $__appUrl = str_replace('\\', '/', substr($__appRoot, strlen($__docRoot)));
    } else {
        $__appUrl = '';
    }
}
define('APP_URL', rtrim($__appUrl, '/'));
unset($__docRoot, $__appRoot, $__appUrl, $envAppUrl);
define('DATE_FORMAT', 'd M Y');
define('TIME_FORMAT', 'g:i A');

// --- Utilization classification thresholds (admin-configurable in DB) -----
// fallback values used when the settings table is unavailable
define('THRESHOLD_UNDER_UTILIZED', 30);   // 0-30%   under-utilized
define('THRESHOLD_NORMAL',         70);   // 31-70%  normal
define('THRESHOLD_OVERCROWDED',    100);  // 71-100% highly utilized, >100% overcrowded

// --- Session / security ---------------------------------------------------
define('SESSION_LIFETIME', 7200);         // 2 hours
define('CSRF_KEY', 'smartcampus_csrf');

date_default_timezone_set(TIMEZONE);
