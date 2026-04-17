<?php
require_once '../config.php';

if (isset($_GET['addToCart'])) {
    $bill_id = intval($_GET['bill_id']);
    $item_id = $_GET['item_id'];
    $quantity = intval($_GET['quantity']);
    $table_id = $_GET['table_id'];
    $currentTime = date('Y-m-d H:i:s'); // Current time
    $lock_name = sprintf("bill_item_%d_%s", $bill_id, $item_id);
    $lock_acquired = false;

    try {
        if (!db_acquire_named_lock($link, $lock_name, 10)) {
            throw new Exception("This cart item is being updated right now. Please try again.");
        }
        $lock_acquired = true;

        db_begin_transaction_with_isolation($link, 'READ COMMITTED');

        $bill_item_stmt = mysqli_prepare($link, "SELECT bill_item_id FROM bill_items WHERE bill_id = ? AND item_id = ? FOR UPDATE");
        mysqli_stmt_bind_param($bill_item_stmt, "is", $bill_id, $item_id);
        mysqli_stmt_execute($bill_item_stmt);
        $bill_item_result = mysqli_stmt_get_result($bill_item_stmt);
        $existing_bill_item = mysqli_fetch_assoc($bill_item_result);
        mysqli_stmt_close($bill_item_stmt);

        if ($existing_bill_item) {
            $update_bill_stmt = mysqli_prepare($link, "UPDATE bill_items SET quantity = quantity + ? WHERE bill_id = ? AND item_id = ?");
            mysqli_stmt_bind_param($update_bill_stmt, "iis", $quantity, $bill_id, $item_id);
            if (!mysqli_stmt_execute($update_bill_stmt)) {
                throw new Exception("Error updating quantity: " . mysqli_stmt_error($update_bill_stmt));
            }
            mysqli_stmt_close($update_bill_stmt);

            $kitchen_stmt = mysqli_prepare($link, "SELECT kitchen_id FROM Kitchen WHERE table_id = ? AND item_id = ? AND time_ended IS NULL FOR UPDATE");
            mysqli_stmt_bind_param($kitchen_stmt, "is", $table_id, $item_id);
            mysqli_stmt_execute($kitchen_stmt);
            $kitchen_result = mysqli_stmt_get_result($kitchen_stmt);
            $existing_kitchen = mysqli_fetch_assoc($kitchen_result);
            mysqli_stmt_close($kitchen_stmt);

            if ($existing_kitchen) {
                $update_kitchen_stmt = mysqli_prepare($link, "UPDATE Kitchen SET quantity = quantity + ?, time_submitted = ? WHERE kitchen_id = ?");
                mysqli_stmt_bind_param($update_kitchen_stmt, "isi", $quantity, $currentTime, $existing_kitchen['kitchen_id']);
                if (!mysqli_stmt_execute($update_kitchen_stmt)) {
                    throw new Exception("Error updating kitchen item: " . mysqli_stmt_error($update_kitchen_stmt));
                }
                mysqli_stmt_close($update_kitchen_stmt);
            } else {
                $insert_kitchen_stmt = mysqli_prepare($link, "INSERT INTO Kitchen (table_id, item_id, quantity, time_submitted) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($insert_kitchen_stmt, "isis", $table_id, $item_id, $quantity, $currentTime);
                if (!mysqli_stmt_execute($insert_kitchen_stmt)) {
                    throw new Exception("Error adding kitchen item: " . mysqli_stmt_error($insert_kitchen_stmt));
                }
                mysqli_stmt_close($insert_kitchen_stmt);
            }
        } else {
            $insert_bill_stmt = mysqli_prepare($link, "INSERT INTO bill_items (bill_id, item_id, quantity) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insert_bill_stmt, "isi", $bill_id, $item_id, $quantity);
            if (!mysqli_stmt_execute($insert_bill_stmt)) {
                throw new Exception("Error adding item to cart: " . mysqli_stmt_error($insert_bill_stmt));
            }
            mysqli_stmt_close($insert_bill_stmt);

            $insert_kitchen_stmt = mysqli_prepare($link, "INSERT INTO Kitchen (table_id, item_id, quantity, time_submitted) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert_kitchen_stmt, "isis", $table_id, $item_id, $quantity, $currentTime);
            if (!mysqli_stmt_execute($insert_kitchen_stmt)) {
                throw new Exception("Error adding kitchen item: " . mysqli_stmt_error($insert_kitchen_stmt));
            }
            mysqli_stmt_close($insert_kitchen_stmt);
        }

        mysqli_commit($link);
        header("Location: orderItem.php?bill_id=" . urlencode($bill_id) . "&table_id=" . $table_id);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        echo '<script>alert(' . json_encode($e->getMessage()) . ')</script>';
    } finally {
        if ($lock_acquired) {
            db_release_named_lock($link, $lock_name);
        }
    }
}
?>
