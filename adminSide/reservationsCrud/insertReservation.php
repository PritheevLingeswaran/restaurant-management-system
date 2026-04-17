<?php
// reservation.php
require_once '../config.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the values from the form
    $customer_name = $_POST["customer_name"];
    $table_id = intval($_POST["table_id"]);
    $reservation_time = $_POST["reservation_time"];
    $reservation_date = $_POST["reservation_date"];
    $special_request = $_POST["special_request"];
    
    $lock_name = sprintf("reservation_%d_%s_%s", $table_id, $reservation_date, $reservation_time);
    $reservation_id = intval($reservation_time) . intval($reservation_date) . intval($table_id);
    $lock_acquired = false;
    $message = "Reservation Created Successfully!";
    $iconClass = 'fa-check-circle';
    $cardClass = 'alert-success';
    $bgColor = "#D4F4DD";

    try {
        if (!db_acquire_named_lock($link, $lock_name, 10)) {
            throw new Exception("The selected table is being booked right now. Please try again.");
        }
        $lock_acquired = true;

        db_begin_transaction_with_isolation($link, 'SERIALIZABLE');

        $capacity_stmt = mysqli_prepare($link, "SELECT capacity FROM restaurant_tables WHERE table_id = ? FOR UPDATE");
        mysqli_stmt_bind_param($capacity_stmt, "i", $table_id);
        mysqli_stmt_execute($capacity_stmt);
        $capacity_result = mysqli_stmt_get_result($capacity_stmt);
        $row = mysqli_fetch_assoc($capacity_result);
        mysqli_stmt_close($capacity_stmt);

        if (!$row) {
            throw new Exception("Selected table was not found.");
        }

        $head_count = $row['capacity'];

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
    } catch (Exception $e) {
        mysqli_rollback($link);
        $message = $e->getMessage();
        $iconClass = 'fa-times-circle';
        $cardClass = 'alert-danger';
        $bgColor = "#FFA7A7";
    } finally {
        if ($lock_acquired) {
            db_release_named_lock($link, $lock_name);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
    <style>
        /* Your custom CSS styles for the success message card here */
        body {
            text-align: center;
            padding: 40px 0;
            background: #EBF0F5;
        }
        h1 {
            color: #88B04B;
            font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
            font-weight: 900;
            font-size: 40px;
            margin-bottom: 10px;
        }
        p {
            color: #404F5E;
            font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
            font-size: 20px;
            margin: 0;
        }
        i.checkmark {
            color: #9ABC66;
            font-size: 100px;
            line-height: 200px;
            margin-left: -15px;
        }
        .card {
            background: white;
            padding: 60px;
            border-radius: 4px;
            box-shadow: 0 2px 3px #C8D0D8;
            display: inline-block;
            margin: 0 auto;
        }
        /* Additional CSS styles based on success/error message */
        .alert-success {
            /* Customize the styles for the success message card */
            background-color: <?php echo $bgColor; ?>;
        }
        .alert-success i {
            color: #5DBE6F; /* Customize the checkmark icon color for success */
        }
        .alert-danger {
            /* Customize the styles for the error message card */
            background-color: #FFA7A7; /* Custom background color for error */
        }
        .alert-danger i {
            color: #F25454; /* Customize the checkmark icon color for error */
        }
        .custom-x {
            color: #F25454; /* Customize the "X" symbol color for error */
            font-size: 100px;
            line-height: 200px;
        }
    </style>
</head>
<body>
    <div class="card <?php echo $cardClass; ?>" style="display: none;">
        <div style="border-radius: 200px; height: 200px; width: 200px; background: #F8FAF5; margin: 0 auto;">
            <?php if ($iconClass === 'fa-check-circle'): ?>
                <i class="checkmark">✓</i>
            <?php else: ?>
                <i class="custom-x" style="font-size: 100px; line-height: 200px;">✘</i>
            <?php endif; ?>
        </div>
        <h1><?php echo ($cardClass === 'alert-success') ? 'Success' : 'Error'; ?></h1>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <div style="text-align: center; margin-top: 20px;">Redirecting back in <span id="countdown">3</span></div>

    <script>
        // Function to show the message card as a pop-up and start the countdown
        function showPopup() {
            var messageCard = document.querySelector(".card");
            messageCard.style.display = "block";

            var i = 3;
            var countdownElement = document.getElementById("countdown");
            var countdownInterval = setInterval(function() {
                i--;
                countdownElement.textContent = i;
                if (i <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = "../panel/reservation-panel.php";
                }
            }, 1000); // 1000 milliseconds = 1 second
        }

        // Show the message card and start the countdown when the page is loaded
        window.onload = showPopup;

        // Function to hide the message card after a delay
        function hidePopup() {
            var messageCard = document.querySelector(".card");
            messageCard.style.display = "none";
            // Redirect to another page after hiding the pop-up (adjust the delay as needed)
            setTimeout(function () {
                window.location.href = "../panel/reservation-panel.php"; // Replace with your desired URL
            }, 3000); // 3000 milliseconds = 3 seconds
        }

        // Hide the message card after 3 seconds (adjust the delay as needed)
        setTimeout(hidePopup, 3000);
    </script>
</body>
</html>
