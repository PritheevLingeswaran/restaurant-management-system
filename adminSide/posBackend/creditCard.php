<?php
session_start(); // Ensure session is started
?>
<?php
require_once '../config.php';
include '../inc/dashHeader.php';
$bill_id = $_GET['bill_id'];
?>

<div class="container" style="margin-top: 15rem; margin-left: 4rem;">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bill (Credit Card Payment)</h3>
                </div>
                <div class="card-body">
                    <h5>Bill ID: <?php echo $bill_id; ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
            <?php
            // Query to fetch cart items for the given bill_id
            $cart_query = "SELECT bi.*, m.item_name, m.item_price FROM bill_items bi
                           JOIN Menu m ON bi.item_id = m.item_id
                           WHERE bi.bill_id = '$bill_id'";
            $cart_result = mysqli_query($link, $cart_query);
            $cart_total = 0;//cart total
            $tax = 0.1; // 10% tax rate

            if ($cart_result && mysqli_num_rows($cart_result) > 0) {
                while ($cart_row = mysqli_fetch_assoc($cart_result)) {
                    $item_id = $cart_row['item_id'];
                    $item_name = $cart_row['item_name'];
                    $item_price = $cart_row['item_price'];
                    $quantity = $cart_row['quantity'];
                    $total = $item_price * $quantity;
                    $bill_item_id = $cart_row['bill_item_id'];
                    $cart_total += $total;
                    echo '<tr>';
                    echo '<td>' . $item_id . '</td>';
                    echo '<td>' . $item_name . '</td>';
                    echo '<td>Rs ' . number_format($item_price,2) . '</td>';
                    echo '<td>' . $quantity . '</td>';
                    echo '<td>Rs ' . number_format($total,2) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6">No Items in Cart.</td></tr>';
            }
            ?>
        </tbody>
                        </table>
                    </div>
                    <hr>
                    <div class="text-right">
                        <?php 
                        echo "<strong>Total:</strong> Rs " . number_format($cart_total, 2) . "<br>";
                        echo "<strong>Tax (10%):</strong> Rs " . number_format($cart_total * $tax, 2) . "<br>";
                        $GRANDTOTAL = $tax * $cart_total + $cart_total;
                        echo "<strong>Grand Total:</strong> Rs " . number_format($GRANDTOTAL, 2);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<div id="card-payment" class="col-md-6 order-md-2" style="margin-top: 10rem; margin-right: 5rem;max-width: 40rem;">
    <div class="container-fluid pt-5 pl-3 pr-3">

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the form
    $account_holder_name = $_POST['cardName'];
    $card_number = $_POST['cardNumber'];
    $expiry_date = $_POST['expiryDate'];
    $security_code = $_POST['securityCode'];
    $bill_id = $_GET['bill_id'];
    $staff_id = $_POST['staff_id'];
    $member_id = intval($_POST['member_id']);
    $reservation_id = $_POST['reservation_id'];
    $GRANDTOTAL = $_POST['GRANDTOTAL'];
    $points = intval($GRANDTOTAL);
    $currentTime = date('Y-m-d H:i:s');
    $lock_name = sprintf('bill_payment_%d', (int) $bill_id);

    try {
        if (!db_acquire_named_lock($link, $lock_name, 10)) {
            throw new Exception("This bill is being paid right now. Please try again.");
        }

        db_begin_transaction_with_isolation($link, 'SERIALIZABLE');

        $bill_stmt = $link->prepare("SELECT card_id, payment_time FROM Bills WHERE bill_id = ? FOR UPDATE");
        $bill_stmt->bind_param("i", $bill_id);
        $bill_stmt->execute();
        $bill_result = $bill_stmt->get_result();
        $bill_row = $bill_result->fetch_assoc();
        $bill_stmt->close();

        if (!$bill_row) {
            throw new Exception("Bill not found.");
        }

        if ($bill_row['card_id'] !== null || $bill_row['payment_time'] !== null) {
            throw new Exception("Bill has already been paid for.");
        }

        $insert_card_stmt = $link->prepare(
            "INSERT INTO card_payments (account_holder_name, card_number, expiry_date, security_code) VALUES (?, ?, ?, ?)"
        );
        $insert_card_stmt->bind_param("ssss", $account_holder_name, $card_number, $expiry_date, $security_code);
        $insert_card_stmt->execute();
        $card_id = $insert_card_stmt->insert_id;
        $insert_card_stmt->close();

        if ($member_id > 0) {
            $points_stmt = $link->prepare("UPDATE Memberships SET points = points + ? WHERE member_id = ?");
            $points_stmt->bind_param("ii", $points, $member_id);
            $points_stmt->execute();
            $points_stmt->close();
        }

        $payment_method = "card";
        $update_bill_stmt = $link->prepare(
            "UPDATE Bills SET card_id = ?, payment_method = ?, payment_time = ?, staff_id = ?, member_id = ?, reservation_id = ? WHERE bill_id = ?"
        );
        $update_bill_stmt->bind_param("issiiii", $card_id, $payment_method, $currentTime, $staff_id, $member_id, $reservation_id, $bill_id);
        $update_bill_stmt->execute();
        $update_bill_stmt->close();

        $link->commit();

        echo '<div class="alert alert-success" role="alert">
        Payment successful!</div>';
        echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
        echo '<br><a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
    } catch (Exception $e) {
        $link->rollback();
        echo '<div class="alert alert-warning" role="alert">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
        if (strpos($e->getMessage(), 'already been paid') !== false) {
            echo '<br><a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
        }
    } finally {
        db_release_named_lock($link, $lock_name);
    }
}
?>
    </div>
    </div><!-- comment -->


<?php include '../inc/dashFooter.php'; ?>
