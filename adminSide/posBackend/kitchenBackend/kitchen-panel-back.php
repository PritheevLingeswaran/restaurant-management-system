<?php
session_start();
require_once '../../config.php';

if (!isset($_GET['action'], $_GET['kitchen_id']) || $_GET['action'] !== 'set_time_ended') {
    header("Location: ../../panel/kitchen-panel.php?status=error&message=Invalid%20kitchen%20request");
    exit();
}

$kitchenId = (int) $_GET['kitchen_id'];
$loggedStaffId = db_get_logged_staff_id($link);

try {
    db_begin_transaction_with_isolation($link, 'READ COMMITTED');

    $kitchenStmt = $link->prepare(
        "SELECT kitchen_id, item_id, quantity, time_ended
         FROM Kitchen
         WHERE kitchen_id = ?
         FOR UPDATE"
    );
    $kitchenStmt->bind_param("i", $kitchenId);
    $kitchenStmt->execute();
    $kitchenResult = $kitchenStmt->get_result();
    $kitchenRow = $kitchenResult->fetch_assoc();
    $kitchenStmt->close();

    if (!$kitchenRow) {
        throw new RuntimeException('Kitchen ticket not found.');
    }

    if ($kitchenRow['time_ended'] !== null) {
        $link->commit();
        header("Location: ../../panel/kitchen-panel.php?status=success&message=Ticket%20already%20completed");
        exit();
    }

    inventory_consume_menu_item(
        $link,
        $kitchenRow['item_id'],
        (int) $kitchenRow['quantity'],
        $kitchenId,
        $loggedStaffId
    );

    $updateStmt = $link->prepare("UPDATE Kitchen SET time_ended = NOW() WHERE kitchen_id = ?");
    $updateStmt->bind_param("i", $kitchenId);
    $updateStmt->execute();
    $updateStmt->close();

    $link->commit();
    header("Location: ../../panel/kitchen-panel.php?status=success&message=Kitchen%20ticket%20completed%20and%20inventory%20updated");
    exit();
} catch (Throwable $exception) {
    $link->rollback();
    header("Location: ../../panel/kitchen-panel.php?status=error&message=" . urlencode($exception->getMessage()));
    exit();
}
?>
