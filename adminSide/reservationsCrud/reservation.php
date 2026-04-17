<?php
// Assuming you have already established a database connection

// reservation.php
require_once '../config.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the values from the form
    $customer_name = $_POST["customer_name"];
    $table_id = intval($_POST["table_id"]);
    $reservation_time = $_POST["reservation_time"];
    $reservation_date = $_POST["reservation_date"];
    $head_count = $_POST["head_count"];
    $special_request = $_POST["special_request"];
    $reservation_id = intval($reservation_time) . intval($reservation_date) . intval($table_id);
    $lock_name = sprintf("reservation_%d_%s_%s", $table_id, $reservation_date, $reservation_time);
    $lock_acquired = false;

    try {
        if (!db_acquire_named_lock($link, $lock_name, 10)) {
            throw new Exception("The selected table is being booked right now. Please try again.");
        }
        $lock_acquired = true;

        db_begin_transaction_with_isolation($link, 'SERIALIZABLE');

        $check_stmt = mysqli_prepare($link, "SELECT reservation_id FROM Reservations WHERE table_id = ? AND reservation_date = ? AND reservation_time = ? FOR UPDATE");
        mysqli_stmt_bind_param($check_stmt, "iss", $table_id, $reservation_date, $reservation_time);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $existing_reservation = mysqli_fetch_assoc($check_result);
        mysqli_stmt_close($check_stmt);

        if ($existing_reservation) {
            throw new Exception("This table has already been reserved for the selected time.");
        }

        $reservation_stmt = mysqli_prepare(
            $link,
            "INSERT INTO Reservations (reservation_id, customer_name, table_id, reservation_time, reservation_date, head_count, special_request) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $reservation_stmt,
            "isissis",
            $reservation_id,
            $customer_name,
            $table_id,
            $reservation_time,
            $reservation_date,
            $head_count,
            $special_request
        );
        if (!mysqli_stmt_execute($reservation_stmt)) {
            throw new Exception("Failed to create reservation: " . mysqli_stmt_error($reservation_stmt));
        }
        mysqli_stmt_close($reservation_stmt);

        $availability_stmt = mysqli_prepare(
            $link,
            "INSERT INTO Table_Availability (availability_id, table_id, reservation_date, reservation_time, status) VALUES (?, ?, ?, ?, 'reserved')"
        );
        mysqli_stmt_bind_param($availability_stmt, "iiss", $reservation_id, $table_id, $reservation_date, $reservation_time);
        if (!mysqli_stmt_execute($availability_stmt)) {
            throw new Exception("Failed to update table availability: " . mysqli_stmt_error($availability_stmt));
        }
        mysqli_stmt_close($availability_stmt);

        mysqli_commit($link);
        $_SESSION['customer_name'] = $customer_name;
        header("Location: success_create_reserve.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($link);
        header("Location: success_create_reserve.php?error=" . urlencode($e->getMessage()));
        exit;
    } finally {
        if ($lock_acquired) {
            db_release_named_lock($link, $lock_name);
        }
    }
}
?>
