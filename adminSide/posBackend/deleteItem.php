<?php
require_once '../config.php';

if (isset($_GET['bill_item_id'])) {
    $bill_item_id = (int) $_GET['bill_item_id'];
    $table_id = (int) $_GET['table_id'];
    $item_id = $_GET['item_id'];
    $lock_name = sprintf("bill_item_%d_%s", (int) $_GET['bill_id'], $item_id);
    $lock_acquired = false;

    try {
        if (!db_acquire_named_lock($link, $lock_name, 10)) {
            throw new Exception("This cart item is being updated right now. Please try again.");
        }
        $lock_acquired = true;

        db_begin_transaction_with_isolation($link, 'READ COMMITTED');

        $delete_stmt = mysqli_prepare($link, "DELETE FROM bill_items WHERE bill_item_id = ?");
        mysqli_stmt_bind_param($delete_stmt, "i", $bill_item_id);
        if (!mysqli_stmt_execute($delete_stmt)) {
            throw new Exception("Error deleting item: " . mysqli_stmt_error($delete_stmt));
        }
        mysqli_stmt_close($delete_stmt);

        $kitchen_stmt = mysqli_prepare($link, "DELETE FROM Kitchen WHERE table_id = ? AND item_id = ? AND time_ended IS NULL");
        mysqli_stmt_bind_param($kitchen_stmt, "is", $table_id, $item_id);
        if (!mysqli_stmt_execute($kitchen_stmt)) {
            throw new Exception("Error deleting kitchen item: " . mysqli_stmt_error($kitchen_stmt));
        }
        mysqli_stmt_close($kitchen_stmt);

        mysqli_commit($link);
        header("Location: orderItem.php?bill_id={$_GET['bill_id']}&delete_success=1&table_id={$table_id}");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        header("Location: orderItem.php?bill_id={$_GET['bill_id']}&delete_error=1&table_id={$table_id}");
        exit();
    } finally {
        if ($lock_acquired) {
            db_release_named_lock($link, $lock_name);
        }
    }
} else {
    // Redirect back to the orderItem.php page if bill_item_id is not provided
    echo "bill_item_id not provided."; // Debug: Display a message
    header("Location: orderItem.php?bill_id={$_GET['bill_id']}&table_id={$table_id}");
    exit();
}
?>
