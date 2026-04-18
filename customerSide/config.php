<?php // Rememeber to change the username,password and database name to acutal values
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurantDB');

//Create Connection
$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$link->set_charset('utf8mb4');
$link->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

//Check COnnection
if ($link->connect_error) { //if not Connection
    die('Connection Failed' . $link->connect_error);//kills the Connection OR terminate execution
}

if (!function_exists('db_begin_transaction_with_isolation')) {
    function db_begin_transaction_with_isolation(mysqli $connection, string $isolation = 'READ COMMITTED'): void
    {
        $allowed = ['READ COMMITTED', 'REPEATABLE READ', 'SERIALIZABLE'];
        if (!in_array($isolation, $allowed, true)) {
            $isolation = 'READ COMMITTED';
        }

        $connection->query("SET TRANSACTION ISOLATION LEVEL {$isolation}");
        $connection->begin_transaction();
    }
}

if (!function_exists('db_acquire_named_lock')) {
    function db_acquire_named_lock(mysqli $connection, string $lockName, int $timeoutSeconds = 10): bool
    {
        $stmt = $connection->prepare("SELECT GET_LOCK(?, ?)");
        $stmt->bind_param("si", $lockName, $timeoutSeconds);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        $stmt->close();

        return (int) $result === 1;
    }
}

if (!function_exists('db_release_named_lock')) {
    function db_release_named_lock(mysqli $connection, string $lockName): void
    {
        $stmt = $connection->prepare("SELECT RELEASE_LOCK(?)");
        $stmt->bind_param("s", $lockName);
        $stmt->execute();
        $stmt->close();
    }
}
?>
