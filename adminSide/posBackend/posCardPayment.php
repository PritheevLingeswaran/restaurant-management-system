<?php
session_start();
require_once '../config.php';
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$bill_id = (int) ($_GET['bill_id'] ?? 0);
$staff_id = (int) ($_GET['staff_id'] ?? 0);
$member_id = (int) ($_GET['member_id'] ?? 0);
$reservation_id = (int) ($_GET['reservation_id'] ?? 0);

$cart_query = "SELECT bi.*, m.item_name, m.item_price
               FROM bill_items bi
               JOIN Menu m ON bi.item_id = m.item_id
               WHERE bi.bill_id = '$bill_id'";
$cart_result = mysqli_query($link, $cart_query);
$cart_total = 0;
$tax = 0.1;
?>

<style>
    .payment-page {
        width: calc(100% - 240px);
        margin-left: 240px;
        padding: 4.75rem 1.5rem 2.5rem;
    }

    .payment-page .legacy-surface + .legacy-surface {
        margin-top: 1.5rem;
    }

    .payment-summary {
        max-width: 420px;
        margin-top: 1rem;
    }

    @media (max-width: 1200px) {
        .payment-page {
            width: 100%;
            margin-left: 0;
            padding: 1rem;
        }
    }
</style>

<div class="payment-page">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <div>
                <h2 class="pull-left">Bill (Credit Card Payment)</h2>
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
                        <tr>
                            <td colspan="5">No Items in Cart.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="payment-summary">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td>Rs <?php echo number_format($cart_total, 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tax (10%)</strong></td>
                        <td>Rs <?php echo number_format($cart_total * $tax, 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Grand Total</strong></td>
                        <td>Rs <?php echo number_format(($tax * $cart_total) + $cart_total, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Card Details</h2>
        </div>

        <form action="creditCard.php?bill_id=<?php echo $bill_id; ?>" method="post" style="max-width: 540px;">
            <div class="form-group mb-3">
                <label for="cardNameField">Account Holder Name</label>
                <input type="text" id="cardNameField" name="cardName" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label for="cardField">Card Number</label>
                <input type="text" id="cardField" name="cardNumber" maxlength="19" minlength="19" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX" pattern="\d{4}-\d{4}-\d{4}-\d{4}" title="Card number must be 16 digits in XXXX-XXXX-XXXX-XXXX format" required>
            </div>
            <div class="form-group mb-3">
                <label for="expiryDate">Expiry Date</label>
                <input type="text" id="expiryDate" name="expiryDate" pattern="(0[1-9]|1[0-2])\/[0-9]{2}" maxlength="5" placeholder="MM/YY" class="form-control" title="Enter expiry date in MM/YY format (e.g. 06/32)" required>
            </div>
            <div class="form-group mb-3">
                <label for="securityCode">Security Code</label>
                <input type="text" id="securityCode" name="securityCode" maxlength="3" class="form-control" placeholder="CCV" pattern="[0-9]{3}" required>
                <small class="form-text text-muted">Please enter a 3-digit security code.</small>
            </div>

            <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
            <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
            <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
            <input type="hidden" name="GRANDTOTAL" value="<?php echo ($tax * $cart_total) + $cart_total; ?>">

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="privacyCheckbox" required>
                <label class="form-check-label" for="privacyCheckbox">I agree to the Private Data Terms and Conditions</label><br>
                <small id="privacyHelp" class="form-text text-muted">By checking the box you understand we will save your credit card information.</small>
            </div>
            <button type="submit" id="cardSubmit" class="btn btn-dark">Pay</button>
        </form>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cardField = document.getElementById('cardField');
    
    cardField.addEventListener('input', function(e) {
        // Strip all non-digit characters
        var digits = this.value.replace(/\D/g, '');
        
        // Limit to 16 digits
        digits = digits.substring(0, 16);
        
        // Format as XXXX-XXXX-XXXX-XXXX
        var formatted = '';
        for (var i = 0; i < digits.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formatted += '-';
            }
            formatted += digits[i];
        }
        
        this.value = formatted;
    });

    // Prevent non-numeric input (allow backspace, delete, arrow keys, tab)
    cardField.addEventListener('keypress', function(e) {
        var char = String.fromCharCode(e.which || e.keyCode);
        if (!/\d/.test(char)) {
            e.preventDefault();
        }
    });

    // Expiry date auto-formatting (MM/YY)
    var expiryField = document.getElementById('expiryDate');
    
    expiryField.addEventListener('input', function(e) {
        var digits = this.value.replace(/\D/g, '');
        digits = digits.substring(0, 4);
        
        if (digits.length >= 2) {
            this.value = digits.substring(0, 2) + '/' + digits.substring(2);
        } else {
            this.value = digits;
        }
    });

    expiryField.addEventListener('keypress', function(e) {
        var char = String.fromCharCode(e.which || e.keyCode);
        if (!/\d/.test(char)) {
            e.preventDefault();
        }
    });

    // Validate on form submit
    var form = cardField.closest('form');
    form.addEventListener('submit', function(e) {
        var cardValue = cardField.value;
        var cardPattern = /^\d{4}-\d{4}-\d{4}-\d{4}$/;
        if (!cardPattern.test(cardValue)) {
            e.preventDefault();
            alert('Card number must be exactly 16 digits in XXXX-XXXX-XXXX-XXXX format.');
            cardField.focus();
            return;
        }

        var expiryValue = expiryField.value;
        var expiryPattern = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!expiryPattern.test(expiryValue)) {
            e.preventDefault();
            alert('Expiry date must be in MM/YY format (e.g. 06/32).');
            expiryField.focus();
            return;
        }
    });
});
</script>

