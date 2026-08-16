<?php
/**
 * DSVV SmartCampus - PDO database connection (singleton)
 *
 * On first connect to an empty database, the schema + seed data are imported
 * automatically (the DROP/CREATE/USE header lines are stripped so the import
 * works on any hosted MySQL where the app user owns only its own database).
 */

require_once __DIR__ . '/config.php';

/**
 * Lazily install the schema + seed when the 'users' table does not exist.
 * Idempotent and guarded by a lock file to survive concurrent cold starts.
 */
function maybe_install_schema(PDO $pdo): void {
    try {
        if ($pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn()) {
            return; // already installed
        }

        $lock = fopen(sys_get_temp_dir() . '/smartcampus_install.lock', 'c');
        if (!$lock) return;
        flock($lock, LOCK_EX);

        // Re-check now that we hold the lock.
        if (!$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn()) {
            $sqlFile = __DIR__ . '/../database/smartcampus.sql';
            if (is_file($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if ($sql !== false && trim($sql) !== '') {
                    $sql = preg_replace('/^DROP\s+DATABASE\s+IF\s+EXISTS\s+`?smartcampus`?;\s*$/mi', '', $sql);
                    $sql = preg_replace('/^CREATE\s+DATABASE\s+`?smartcampus`?[^;]*;\s*$/mi', '', $sql);
                    $sql = preg_replace('/^USE\s+`?smartcampus`?;\s*$/mi', '', $sql);
                    $pdo->exec($sql);
                }
            }
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    } catch (Throwable $e) {
        // Schema already present / no privilege / partial install: never crash
        // the request here; the normal DB flow will surface real problems.
        error_log('[DSVV SmartCampus] auto-install skipped: ' . $e->getMessage());
    }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            maybe_install_schema($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Check the DB_* settings (config/config.php or environment variables) and ensure MySQL is running.<br>' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
