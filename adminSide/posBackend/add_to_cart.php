<?php
require_once '../config.php';

$bill_id = $_POST['bill_id'];
$item_id = $_POST['item_id'];
$lock_name = sprintf("bill_item_%d_%s", (int) $bill_id, $item_id);
$lock_acquired = false;

try {
    if (!db_acquire_named_lock($link, $lock_name, 10)) {
        throw new Exception("This cart item is being updated right now. Please try again.");
    }
    $lock_acquired = true;

    db_begin_transaction_with_isolation($link, 'READ COMMITTED');

    $existing_stmt = mysqli_prepare($link, "SELECT bill_item_id FROM Bill_Items WHERE bill_id = ? AND item_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($existing_stmt, "is", $bill_id, $item_id);
    mysqli_stmt_execute($existing_stmt);
    $existing_result = mysqli_stmt_get_result($existing_stmt);
    $existing_item = mysqli_fetch_assoc($existing_result);
    mysqli_stmt_close($existing_stmt);

    if ($existing_item) {
        $update_stmt = mysqli_prepare($link, "UPDATE Bill_Items SET quantity = quantity + 1 WHERE bill_id = ? AND item_id = ?");
        mysqli_stmt_bind_param($update_stmt, "is", $bill_id, $item_id);
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception(mysqli_stmt_error($update_stmt));
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $insert_stmt = mysqli_prepare($link, "INSERT INTO Bill_Items (bill_id, item_id, quantity) VALUES (?, ?, 1)");
        mysqli_stmt_bind_param($insert_stmt, "is", $bill_id, $item_id);
        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception(mysqli_stmt_error($insert_stmt));
        }
        mysqli_stmt_close($insert_stmt);
    }

    mysqli_commit($link);
} catch (Exception $e) {
    mysqli_rollback($link);
    http_response_code(409);
    echo $e->getMessage();
} finally {
    if ($lock_acquired) {
        db_release_named_lock($link, $lock_name);
    }

    mysqli_close($link);
}
?>
