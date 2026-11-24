<?php
// ── Default XAMPP credentials (user "root", empty password)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'food_blog');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Returns the shared mysqli connection.
 *
 * First run: if the "food_blog" database does not exist yet, config/schema.sql
 * is imported automatically (tables + demo data), so the project works right
 * after copying it to htdocs. You can also import schema.sql manually.
 */
function getDB(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = mysqli_init();
        // Return INT/FLOAT columns as real PHP numbers (keeps === comparisons on ids safe)
        mysqli_options($conn, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, null, DB_PORT);
        mysqli_set_charset($conn, 'utf8mb4');

        if (!databaseExists($conn)) {
            importSchema($conn);
        }
        mysqli_select_db($conn, DB_NAME);
    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        die('<h2>Database connection failed</h2><p>Make sure Apache <strong>and MySQL</strong> are running in the '
          . 'XAMPP Control Panel, then reload this page.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
    }

    return $conn;
}

function databaseExists(mysqli $conn): bool {
    $stmt = mysqli_prepare($conn, 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
    $name = DB_NAME;
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
    mysqli_stmt_close($stmt);
    return $exists;
}

function importSchema(mysqli $conn): void {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('config/schema.sql not found.');
    }
    mysqli_multi_query($conn, $sql);
    // Flush every result so the connection is usable afterwards
    do {
        if ($res = mysqli_store_result($conn)) mysqli_free_result($res);
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
}

// ── Small prepared-statement helpers (keep the models short and injection-safe)
function dbAll(string $sql, string $types = '', array $params = []): array {
    $stmt = mysqli_prepare(getDB(), $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

function dbOne(string $sql, string $types = '', array $params = []): ?array {
    return dbAll($sql, $types, $params)[0] ?? null;
}

/** Runs INSERT/UPDATE/DELETE. Returns the insert id (0 for non-inserts). */
function dbExec(string $sql, string $types = '', array $params = []): int {
    $db   = getDB();
    $stmt = mysqli_prepare($db, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $id = (int)mysqli_insert_id($db);
    mysqli_stmt_close($stmt);
    return $id;
}

function dbScalar(string $sql, string $types = '', array $params = []): int {
    $row = dbOne($sql, $types, $params);
    return $row ? (int)array_values($row)[0] : 0;
}
