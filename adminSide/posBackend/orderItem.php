<?php
session_start();
require_once '../config.php';
include '../inc/dashHeader.php';

$bill_id = (int) ($_GET['bill_id'] ?? 0);
$table_id = (int) ($_GET['table_id'] ?? 0);
$search = trim($_POST['search'] ?? '');

$menuQuery = "SELECT * FROM Menu ORDER BY item_id";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $menuQuery = "SELECT * FROM Menu
                  WHERE item_type LIKE '%$escaped%'
                     OR item_category LIKE '%$escaped%'
                     OR item_name LIKE '%$escaped%'
                     OR item_id LIKE '%$escaped%'
                  ORDER BY item_id";
}
$menuResult = mysqli_query($link, $menuQuery);

$payment_time_query = "SELECT payment_time FROM Bills WHERE bill_id = '$bill_id'";
$payment_time_result = mysqli_query($link, $payment_time_query);
$has_payment_time = false;
if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
    $payment_time_row = mysqli_fetch_assoc($payment_time_result);
    if (!empty($payment_time_row['payment_time'])) {
        $has_payment_time = true;
    }
}

$cart_query = "SELECT bi.*, m.item_name, m.item_price
               FROM bill_items bi
               JOIN Menu m ON bi.item_id = m.item_id
               WHERE bi.bill_id = '$bill_id'";
$cart_result = mysqli_query($link, $cart_query);
$cart_total = 0;
$tax = 0.1;
?>

<!DOCTYPE html>
<html>
<head>
    <link href="../css/pos.css" rel="stylesheet" />
    <style>
        .order-layout-shell {
            width: calc(100% - 240px);
            margin-left: 240px;
            padding: 4.75rem 1.5rem 2.5rem;
        }

        .order-layout-inner {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            padding: 1.75rem;
        }

        .order-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(360px, 0.9fr);
            gap: 1.5rem;
            align-items: start;
        }

        .order-panel h2,
        .order-panel h3 {
            margin: 0 0 1rem;
        }

        .order-toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto auto;
            gap: 0.75rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .order-scroll {
            max-height: 45rem;
            overflow: auto;
        }

        .order-scroll.cart-scroll {
            max-height: 22rem;
        }

        .order-table {
            margin-bottom: 0;
        }

        .order-menu-table {
            table-layout: fixed;
            width: 100%;
        }

        .order-menu-table th:nth-child(1),
        .order-menu-table td:nth-child(1) {
            width: 80px;
        }

        .order-menu-table th:nth-child(2),
        .order-menu-table td:nth-child(2) {
            width: 38%;
        }

        .order-menu-table th:nth-child(3),
        .order-menu-table td:nth-child(3) {
            width: 18%;
        }

        .order-menu-table th:nth-child(4),
        .order-menu-table td:nth-child(4) {
            width: 18%;
            white-space: nowrap;
        }

        .order-menu-table th:nth-child(5),
        .order-menu-table td:nth-child(5) {
            width: 22%;
        }

        .order-table td,
        .order-table th {
            vertical-align: top;
        }

        .order-add-form {
            display: grid;
            gap: 0.5rem;
        }

        .order-summary {
            margin-top: 1.25rem;
        }

        .order-actions {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        @media (max-width: 1200px) {
            .order-layout-shell {
                width: 100%;
                margin-left: 0;
                padding: 1rem;
            }

            .order-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .order-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="order-layout-shell">
        <div class="order-layout-inner">
            <div class="order-grid">
                <div class="order-panel">
                    <h2>Food & Drinks</h2>
                    <form method="POST" action="#" class="order-toolbar">
                        <input type="text" id="search" name="search" class="form-control" placeholder="Search Food & Drinks" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-dark">Search</button>
                        <a href="orderItem.php?bill_id=<?php echo $bill_id; ?>&table_id=<?php echo $table_id; ?>" class="btn btn-light">Show All</a>
                    </form>

                    <div class="order-scroll">
                        <?php if ($menuResult && mysqli_num_rows($menuResult) > 0): ?>
                            <table class="table table-bordered table-striped order-table order-menu-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Add</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_array($menuResult)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['item_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_category']); ?></td>
                                            <td>Rs <?php echo number_format((float) $row['item_price'], 2); ?></td>
                                            <td>
                                                <?php if (!$has_payment_time): ?>
                                                    <form method="get" action="addItem.php" class="order-add-form">
                                                        <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                                                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($row['item_id']); ?>">
                                                        <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                                                        <input type="number" name="quantity" class="form-control" placeholder="1 to 1000" required min="1" max="1000">
                                                        <input type="hidden" name="addToCart" value="1">
                                                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                                                    </form>
                                                <?php else: ?>
                                                    Bill Paid
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-danger mb-0"><em>No menu items were found.</em></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-panel">
                    <h2>Cart</h2>
                    <div class="order-scroll cart-scroll">
                        <table class="table table-bordered order-table">
                            <thead>
                                <tr>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($cart_result && mysqli_num_rows($cart_result) > 0): ?>
                                    <?php while ($cart_row = mysqli_fetch_assoc($cart_result)): ?>
                                        <?php
                                        $item_id = $cart_row['item_id'];
                                        $item_name = $cart_row['item_name'];
                                        $item_price = $cart_row['item_price'];
                                        $quantity = $cart_row['quantity'];
                                        $total = $item_price * $quantity;
                                        $bill_item_id = $cart_row['bill_item_id'];
                                        $cart_total += $total;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item_id); ?></td>
                                            <td><?php echo htmlspecialchars($item_name); ?></td>
                                            <td>Rs <?php echo number_format((float) $item_price, 2); ?></td>
                                            <td><?php echo (int) $quantity; ?></td>
                                            <td>Rs <?php echo number_format((float) $total, 2); ?></td>
                                            <td>
                                                <?php if (!$has_payment_time): ?>
                                                    <a class="btn btn-dark btn-sm" href="deleteItem.php?bill_id=<?php echo $bill_id; ?>&table_id=<?php echo $table_id; ?>&bill_item_id=<?php echo $bill_item_id; ?>&item_id=<?php echo urlencode($item_id); ?>">Delete</a>
                                                <?php else: ?>
                                                    Bill Paid
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No Items in Cart.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="order-summary table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td><strong>Cart Total</strong></td>
                                    <td>Rs <?php echo number_format($cart_total, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Cart Taxed</strong></td>
                                    <td>Rs <?php echo number_format($cart_total * $tax, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Grand Total</strong></td>
                                    <td>Rs <?php echo number_format(($tax * $cart_total) + $cart_total, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="order-actions">
                        <?php if ($has_payment_time): ?>
                            <div class="alert alert-success mb-0">Bill has already been paid.</div>
                            <a href="receipt.php?bill_id=<?php echo $bill_id; ?>" class="btn btn-light">Print Receipt</a>
                        <?php elseif (($tax * $cart_total + $cart_total) > 0): ?>
                            <a href="idValidity.php?bill_id=<?php echo $bill_id; ?>" class="btn btn-success">Pay Bill</a>
                        <?php else: ?>
                            <h3 class="h4 mb-0">Add Item To Cart to Proceed</h3>
                        <?php endif; ?>

                        <form action="newCustomer.php" method="get" class="mb-0">
                            <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                            <button type="submit" name="new_customer" value="true" class="btn btn-warning">New Customer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php include '../inc/dashFooter.php'; ?>
