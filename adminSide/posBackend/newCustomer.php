<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Seating</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5 text-center">
    <?php
    require_once '../config.php';

    if (isset($_GET['new_customer']) && $_GET['new_customer'] === 'true') {
        $table_id = (int) $_GET['table_id'];
        $bill_time = date('Y-m-d H:i:s');
        $lock_name = sprintf('table_bill_%d', $table_id);
        $bill_id = null;

        try {
            if (!db_acquire_named_lock($link, $lock_name, 10)) {
                throw new Exception('This table is being assigned right now. Please try again.');
            }

            db_begin_transaction_with_isolation($link, 'SERIALIZABLE');

            $existing_stmt = $link->prepare("SELECT bill_id FROM Bills WHERE table_id = ? AND payment_time IS NULL ORDER BY bill_time DESC LIMIT 1 FOR UPDATE");
            $existing_stmt->bind_param("i", $table_id);
            $existing_stmt->execute();
            $existing_result = $existing_stmt->get_result();
            $existing_bill = $existing_result->fetch_assoc();
            $existing_stmt->close();

            if ($existing_bill) {
                $bill_id = (int) $existing_bill['bill_id'];
            } else {
                $insert_stmt = $link->prepare("INSERT INTO Bills (table_id, bill_time) VALUES (?, ?)");
                $insert_stmt->bind_param("is", $table_id, $bill_time);
                $insert_stmt->execute();
                $bill_id = $insert_stmt->insert_id;
                $insert_stmt->close();
            }

            $link->commit();
            echo "<h2>Boundless</h2>";
            echo "<p>You're now seated at Table ID: $table_id</p>";
            echo "<p>Your bill has been created with Bill ID: $bill_id</p>";
            echo '<a href="orderItem.php?bill_id=' . $bill_id . '&table_id=' . $table_id . '" class="btn btn-primary">Back</a>';
        } catch (Exception $e) {
            $link->rollback();
            echo "<div class='alert alert-danger'>".$e->getMessage()."</div>";
        } finally {
            db_release_named_lock($link, $lock_name);
        }
    }
    ?>

</div>

<!-- Add Bootstrap JS and jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
