<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';

$feedback = null;
$feedbackType = 'success';
$loggedStaffId = db_get_logged_staff_id($link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        db_begin_transaction_with_isolation($link, 'READ COMMITTED');

        if (isset($_POST['create_purchase_order'])) {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $ingredientId = trim($_POST['ingredient_id'] ?? '');
            $orderedQty = (float) ($_POST['ordered_qty'] ?? 0);
            $unitCost = (float) ($_POST['unit_cost'] ?? 0);
            $expectedDate = $_POST['expected_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');

            $insertPoStmt = $link->prepare(
                "INSERT INTO Purchase_Orders
                    (supplier_id, order_date, expected_date, status, subtotal, tax_amount, total_amount, notes, created_by)
                 VALUES (?, CURDATE(), ?, 'ordered', ?, 0, ?, ?, ?)"
            );
            $lineTotal = $orderedQty * $unitCost;
            $insertPoStmt->bind_param("idddsi", $supplierId, $expectedDate, $lineTotal, $lineTotal, $notes, $loggedStaffId);
            $insertPoStmt->execute();
            $purchaseOrderId = $insertPoStmt->insert_id;
            $insertPoStmt->close();

            $insertItemStmt = $link->prepare(
                "INSERT INTO Purchase_Order_Items
                    (po_id, ingredient_id, ordered_qty, received_qty, unit_cost, line_total)
                 VALUES (?, ?, ?, 0, ?, ?)"
            );
            $insertItemStmt->bind_param("isddd", $purchaseOrderId, $ingredientId, $orderedQty, $unitCost, $lineTotal);
            $insertItemStmt->execute();
            $insertItemStmt->close();

            $feedback = 'Purchase order created successfully.';
        }

        if (isset($_POST['receive_purchase_order'])) {
            $purchaseOrderId = (int) ($_POST['po_id'] ?? 0);
            inventory_receive_purchase_order($link, $purchaseOrderId, $loggedStaffId);
            $feedback = 'Purchase order received and inventory updated.';
        }

        $link->commit();
    } catch (Throwable $exception) {
        $link->rollback();
        $feedback = $exception->getMessage();
        $feedbackType = 'danger';
    }
}

$poSummary = $link->query(
    "SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN status = 'ordered' THEN 1 ELSE 0 END), 0) AS pending_orders,
        COALESCE(SUM(CASE WHEN status = 'received' THEN total_amount ELSE 0 END), 0) AS received_value
     FROM Purchase_Orders"
)->fetch_assoc();

$purchaseOrders = $link->query(
    "SELECT po.po_id, po.order_date, po.expected_date, po.received_at, po.status, po.total_amount,
            s.supplier_name
     FROM Purchase_Orders po
     INNER JOIN Inventory_Suppliers s ON s.supplier_id = po.supplier_id
     ORDER BY po.po_id DESC"
);

$purchaseOrderItems = $link->query(
    "SELECT poi.po_id, i.ingredient_name, poi.ordered_qty, poi.received_qty, poi.unit_cost
     FROM Purchase_Order_Items poi
     INNER JOIN Inventory_Ingredients i ON i.ingredient_id = poi.ingredient_id
     ORDER BY poi.po_id DESC, poi.po_item_id ASC"
);

$poItemsByOrder = [];
while ($poItem = $purchaseOrderItems->fetch_assoc()) {
    $poItemsByOrder[$poItem['po_id']][] = $poItem;
}
?>
<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php include '../inc/inventoryPanelStyles.php'; ?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="ops-head">
            <div>
                <h2>Purchase Orders</h2>
                <p>Create procurement orders, receive deliveries into stock, and maintain an auditable stock-in workflow linked directly to supplier pricing and inventory valuation.</p>
            </div>
        </div>

        <?php if ($feedback !== null): ?>
            <div class="alert alert-<?php echo $feedbackType; ?>"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>

        <div class="ops-grid">
            <div class="ops-card">
                <span>Total Orders</span>
                <strong><?php echo (int) $poSummary['total_orders']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Pending Receipts</span>
                <strong><?php echo (int) $poSummary['pending_orders']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Received Value</span>
                <strong>Rs <?php echo number_format((float) $poSummary['received_value'], 2); ?></strong>
            </div>
        </div>

        <div class="ops-columns">
            <div class="ops-panel">
                <h3>Create Quick Purchase Order</h3>
                <form class="ops-form" method="post">
                    <div>
                        <label for="supplier_id">Supplier</label>
                        <select id="supplier_id" name="supplier_id" required>
                            <option value="">Select supplier</option>
                            <?php
                            $supplierOptions = $link->query("SELECT supplier_id, supplier_name FROM Inventory_Suppliers WHERE is_active = 1 ORDER BY supplier_name");
                            while ($supplier = $supplierOptions->fetch_assoc()):
                            ?>
                                <option value="<?php echo (int) $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ingredient_id">Ingredient</label>
                        <select id="ingredient_id" name="ingredient_id" required>
                            <option value="">Select ingredient</option>
                            <?php
                            $ingredientOptions = $link->query("SELECT ingredient_id, ingredient_name FROM Inventory_Ingredients WHERE is_active = 1 ORDER BY ingredient_name");
                            while ($ingredient = $ingredientOptions->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($ingredient['ingredient_id']); ?>"><?php echo htmlspecialchars($ingredient['ingredient_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ordered_qty">Ordered Quantity</label>
                        <input id="ordered_qty" type="number" step="0.001" min="0.001" name="ordered_qty" required>
                    </div>
                    <div>
                        <label for="unit_cost">Unit Cost</label>
                        <input id="unit_cost" type="number" step="0.01" min="0.01" name="unit_cost" required>
                    </div>
                    <div>
                        <label for="expected_date">Expected Date</label>
                        <input id="expected_date" type="date" name="expected_date" value="<?php echo date('Y-m-d', strtotime('+2 day')); ?>" required>
                    </div>
                    <div class="full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Examples: morning vegetable lot, weekly meat replenishment, beverage bulk refill"></textarea>
                    </div>
                    <div>
                        <button class="ops-button" type="submit" name="create_purchase_order" value="1">Create Purchase Order</button>
                    </div>
                </form>
            </div>

            <div class="ops-panel">
                <h3>Receiving Notes</h3>
                <ul class="ops-list">
                    <li>Use purchase orders for supplier-linked stock-in instead of ad hoc balance edits whenever the stock comes from procurement.</li>
                    <li>Receiving a purchase order adds quantities into inventory, updates average unit cost, and automatically re-checks low-stock alerts.</li>
                    <li>Pending orders stay visible until fully received, which keeps procurement and kitchen replenishment aligned.</li>
                </ul>
            </div>
        </div>

        <div class="ops-panel">
            <h3>Order Register</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>PO</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Schedule</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($purchaseOrder = $purchaseOrders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo (int) $purchaseOrder['po_id']; ?></td>
                            <td><?php echo htmlspecialchars($purchaseOrder['supplier_name']); ?></td>
                            <td>
                                <?php if (!empty($poItemsByOrder[$purchaseOrder['po_id']])): ?>
                                    <?php foreach ($poItemsByOrder[$purchaseOrder['po_id']] as $line): ?>
                                        <div>
                                            <?php echo htmlspecialchars($line['ingredient_name']); ?>:
                                            <?php echo number_format((float) $line['ordered_qty'], 3); ?>
                                            (received <?php echo number_format((float) $line['received_qty'], 3); ?>)
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                Ordered <?php echo htmlspecialchars($purchaseOrder['order_date']); ?><br>
                                <small>Expected <?php echo htmlspecialchars($purchaseOrder['expected_date']); ?></small>
                            </td>
                            <td>Rs <?php echo number_format((float) $purchaseOrder['total_amount'], 2); ?></td>
                            <td>
                                <?php if ($purchaseOrder['status'] === 'received'): ?>
                                    <span class="ops-badge good">Received</span>
                                <?php else: ?>
                                    <span class="ops-badge pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($purchaseOrder['status'] !== 'received'): ?>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="po_id" value="<?php echo (int) $purchaseOrder['po_id']; ?>">
                                        <button class="ops-button alt" type="submit" name="receive_purchase_order" value="1">Receive</button>
                                    </form>
                                <?php else: ?>
                                    <small><?php echo htmlspecialchars($purchaseOrder['received_at'] ?? 'Completed'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
