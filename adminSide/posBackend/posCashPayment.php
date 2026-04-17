<?php
session_start(); // Ensure session is started
?>
<?php
require_once '../config.php';
include '../inc/dashHeader.php'; 
$bill_id = $_GET['bill_id'];
$staff_id = $_GET['staff_id'];
$member_id = intval($_GET['member_id']);
$reservation_id = $_GET['reservation_id'];
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Bill (Cash Payment)</h3>
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
                    echo '<td>Rs ' . $item_price . '</td>';
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
            
            

<div id="cash-payment" class="container-fluid mt-5 pt-5 pl-5 pr-5 mb-5">
    <div class="row">
        <div class="col-md-6">
            <h1>Cash Payment</h1>
            <form action="" method="get">
                <div class="form-group">
                    <label for="payment_amount">Payment Amount</label>
                    <input type="number" min="0" id="payment_amount" name="payment_amount" class="form-control" required>
                </div>

                <!-- Add hidden input fields for bill_id, staff_id, member_id, and reservation_id -->
                <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
                <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
                <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                <input type="hidden" name="GRANDTOTAL" value="<?php echo $tax * $cart_total + $cart_total; ?>">

                <button type="submit" id="cardSubmit" class="btn btn-dark mt-2">Pay</button>
            </form>
        </div>
        <div class="col-md-6">
        <?php
        function calculateChange(float $paymentAmount, float $GrandTotal) {
            return $paymentAmount - $GrandTotal;
        }
        
        

        if (isset($_GET['payment_amount'])) {
            $payment_amount = isset($_GET['payment_amount']) ? floatval($_GET['payment_amount']) : 0.0;

            if ($payment_amount >= $GRANDTOTAL) {
                echo '<div class="alert alert-dark" role="alert">';
                echo "Change is Rs " . number_format(calculateChange($payment_amount, $GRANDTOTAL),2);
                echo '</div>';

                $currentTime = date('Y-m-d H:i:s');
                $points = intval($GRANDTOTAL);
                $lock_name = sprintf('bill_payment_%d', (int) $bill_id);

                try {
                    if (!db_acquire_named_lock($link, $lock_name, 10)) {
                        throw new Exception("This bill is being paid right now. Please try again.");
                    }

                    db_begin_transaction_with_isolation($link, 'SERIALIZABLE');

                    $bill_stmt = $link->prepare("SELECT payment_time FROM Bills WHERE bill_id = ? FOR UPDATE");
                    $bill_stmt->bind_param("i", $bill_id);
                    $bill_stmt->execute();
                    $bill_result = $bill_stmt->get_result();
                    $bill_row = $bill_result->fetch_assoc();
                    $bill_stmt->close();

                    if (!$bill_row) {
                        throw new Exception("Bill not found.");
                    }

                    if ($bill_row['payment_time'] !== null) {
                        throw new Exception("Bill with ID $bill_id has already been paid.");
                    }

                    $update_stmt = $link->prepare("UPDATE Bills SET payment_method = 'cash', payment_time = ?, staff_id = ?, member_id = ?, reservation_id = ? WHERE bill_id = ?");
                    $update_stmt->bind_param("siiii", $currentTime, $staff_id, $member_id, $reservation_id, $bill_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    if ($member_id > 0) {
                        $points_stmt = $link->prepare("UPDATE Memberships SET points = points + ? WHERE member_id = ?");
                        $points_stmt->bind_param("ii", $points, $member_id);
                        $points_stmt->execute();
                        $points_stmt->close();
                    }

                    $link->commit();
                    echo '<div class="alert alert-success" role="alert">
                            Bill successfully Paid!
                          </div>';
                    echo '<a href="posTable.php" class="btn btn-dark ">Back to Tables</a>';
                    echo '<a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
                } catch (Exception $e) {
                    $link->rollback();
                    echo '<div class="alert alert-warning" role="alert">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                    echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
                } finally {
                    db_release_named_lock($link, $lock_name);
                }
            } else {
                echo '<div class="alert alert-warning" role="alert">
                        Payment amount is not sufficient
                      </div>';
                echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
            }
        }
        ?>

    </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
