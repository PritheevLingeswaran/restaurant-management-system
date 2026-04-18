<?php
session_start();
require_once '../../config.php';

$loggedStaffId = db_get_logged_staff_id($link);

try {
    db_begin_transaction_with_isolation($link, 'READ COMMITTED');

    $selectStmt = $link->prepare(
        "SELECT kitchen_id
         FROM Kitchen
         WHERE time_ended IS NOT NULL
         ORDER BY time_ended DESC
         LIMIT 1
         FOR UPDATE"
    );
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $row = $result->fetch_assoc();
    $selectStmt->close();

    if (!$row) {
        throw new RuntimeException('No completed kitchen ticket is available to undo.');
    }

    $kitchenId = (int) $row['kitchen_id'];
    inventory_reverse_kitchen_consumption($link, $kitchenId, $loggedStaffId);

    $updateStmt = $link->prepare("UPDATE Kitchen SET time_ended = NULL WHERE kitchen_id = ?");
    $updateStmt->bind_param("i", $kitchenId);
    $updateStmt->execute();
    $updateStmt->close();

    $link->commit();
    header("Location: ../../panel/kitchen-panel.php?status=success&message=Last%20kitchen%20completion%20was%20reversed");
    exit();
} catch (Throwable $exception) {
    $link->rollback();
    header("Location: ../../panel/kitchen-panel.php?status=error&message=" . urlencode($exception->getMessage()));
    exit();
}
?>
