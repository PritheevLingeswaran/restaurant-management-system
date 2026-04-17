<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurantDB');

$setupStatus = 'ready';
$setupMessage = 'Setup has already been completed. You can open the customer site below.';

if (!file_exists('setup_completed.flag')) {
    try {
        $link = new mysqli(DB_HOST, DB_USER, DB_PASS);
        $link->set_charset('utf8mb4');
        $link->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
        $link->select_db(DB_NAME);

        $sql = file_get_contents('restaurantDB.txt');
        if ($sql === false) {
            throw new RuntimeException('Unable to read restaurantDB.txt');
        }

        if ($link->multi_query($sql)) {
            do {
                if ($result = $link->store_result()) {
                    $result->free();
                }
            } while ($link->more_results() && $link->next_result());
        }

        file_put_contents('setup_completed.flag', 'Setup completed successfully.');
        $setupStatus = 'success';
        $setupMessage = 'Database setup completed successfully. The project is ready to use.';
        $link->close();
    } catch (Throwable $e) {
        $setupStatus = 'error';
        $setupMessage = 'Setup could not be completed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management System</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: min(700px, 92vw);
            background: rgba(15, 23, 42, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        }
        h1 {
            margin-top: 0;
            font-size: 2rem;
        }
        p {
            line-height: 1.6;
            color: #dbe4ee;
        }
        .status {
            display: inline-block;
            margin: 12px 0 20px;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.1);
        }
        .status.success { background: rgba(34, 197, 94, 0.18); color: #bbf7d0; }
        .status.ready { background: rgba(59, 130, 246, 0.18); color: #bfdbfe; }
        .status.error { background: rgba(239, 68, 68, 0.18); color: #fecaca; }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }
        .button {
            text-decoration: none;
            color: #fff;
            background: #f97316;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 600;
        }
        .button.secondary {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Restaurant Management System</h1>
        <div class="status <?php echo htmlspecialchars($setupStatus, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo strtoupper(htmlspecialchars($setupStatus, ENT_QUOTES, 'UTF-8')); ?>
        </div>
        <p><?php echo htmlspecialchars($setupMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>This project includes customer reservations, staff login, POS billing, kitchen handling, and revenue reporting.</p>
        <div class="actions">
            <a class="button" href="customerSide/home/home.php">Open Customer Site</a>
            <a class="button secondary" href="adminSide/StaffLogin/login.php">Open Staff Login</a>
        </div>
    </div>
</body>
</html>
