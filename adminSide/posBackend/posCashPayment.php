<?php
session_start();
require_once '../config.php';
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$bill_id = $_GET['bill_id'];
$staff_id = $_GET['staff_id'];
$member_id = intval($_GET['member_id']);
$reservation_id = $_GET['reservation_id'];

$cart_query = "SELECT bi.*, m.item_name, m.item_price FROM bill_items bi
               JOIN Menu m ON bi.item_id = m.item_id
               WHERE bi.bill_id = '$bill_id'";
$cart_result = mysqli_query($link, $cart_query);
$cart_total = 0;
$tax = 0.1;
$GRANDTOTAL = 0;
?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 980px;">
        <div class="legacy-toolbar">
            <div>
                <h2 class="pull-left">Bill (Cash Payment)</h2>
                <p class="text-muted mb-0">Bill ID: <?php echo $bill_id; ?></p>
            </div>
        </div>

        <div class="legacy-table-wrap">
            <table class="table table-bordered table-striped">
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
                    <?php if ($cart_result && mysqli_num_rows($cart_result) > 0): ?>
                        <?php while ($cart_row = mysqli_fetch_assoc($cart_result)): ?>
                            <?php
                            $item_price = (float) $cart_row['item_price'];
                            $quantity = (int) $cart_row['quantity'];
                            $total = $item_price * $quantity;
                            $cart_total += $total;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cart_row['item_id']); ?></td>
                                <td><?php echo htmlspecialchars($cart_row['item_name']); ?></td>
                                <td>Rs <?php echo number_format($item_price, 2); ?></td>
                                <td><?php echo $quantity; ?></td>
                                <td>Rs <?php echo number_format($total, 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No Items in Cart.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $GRANDTOTAL = $tax * $cart_total + $cart_total; ?>
        <div class="legacy-table-wrap narrow-table" style="max-width: 420px; margin-top: 1rem;">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr><td><strong>Total</strong></td><td>Rs <?php echo number_format($cart_total, 2); ?></td></tr>
                    <tr><td><strong>Tax (10%)</strong></td><td>Rs <?php echo number_format($cart_total * $tax, 2); ?></td></tr>
                    <tr><td><strong>Grand Total</strong></td><td>Rs <?php echo number_format($GRANDTOTAL, 2); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="legacy-surface" style="max-width: 980px; margin-top: 1.5rem;">
        <h2 class="pull-left">Cash Payment</h2>
        <form action="" method="get" style="max-width: 420px;">
            <div class="form-group">
                <label for="payment_amount">Payment Amount</label>
                <input type="number" min="0" id="payment_amount" name="payment_amount" class="form-control" required>
            </div>
            <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
            <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
            <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
            <input type="hidden" name="GRANDTOTAL" value="<?php echo $GRANDTOTAL; ?>">
            <button type="submit" class="btn btn-dark mt-2">Pay</button>
        </form>

        <?php
        function calculateChange(float $paymentAmount, float $grandTotal): float {
            return $paymentAmount - $grandTotal;
        }

        if (isset($_GET['payment_amount'])) {
            $payment_amount = floatval($_GET['payment_amount']);

            if ($payment_amount >= $GRANDTOTAL) {
                echo '<div class="alert alert-dark mt-3">Change is Rs ' . number_format(calculateChange($payment_amount, $GRANDTOTAL), 2) . '</div>';

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
                    echo '<div class="alert alert-success mt-3">Bill successfully paid!</div>';
                    echo '<div style="display:flex; gap:0.75rem; flex-wrap:wrap;">';
                    echo '<a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
                    echo '<a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt</a>';
                    echo '</div>';
                } catch (Exception $e) {
                    $link->rollback();
                    echo '<div class="alert alert-warning mt-3">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                    echo '<a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
                } finally {
                    db_release_named_lock($link, $lock_name);
                }
            } else {
                echo '<div class="alert alert-warning mt-3">Payment amount is not sufficient.</div>';
                echo '<a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
            }
        }
        ?>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
