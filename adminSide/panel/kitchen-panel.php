<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';

$kitchenOrders = $link->query(
    "SELECT
        k.kitchen_id,
        k.table_id,
        k.item_id,
        m.item_name,
        k.quantity,
        k.time_submitted,
        k.time_ended,
        CASE WHEN EXISTS (SELECT 1 FROM Menu_Ingredients mi WHERE mi.item_id = k.item_id) THEN 1 ELSE 0 END AS has_recipe
     FROM Kitchen k
     INNER JOIN Menu m ON m.item_id = k.item_id
     WHERE k.time_ended IS NULL
     ORDER BY k.time_submitted ASC"
);

$kitchenSummary = $link->query(
    "SELECT
        COALESCE(SUM(CASE WHEN time_ended IS NULL THEN 1 ELSE 0 END), 0) AS active_tickets,
        COALESCE(SUM(CASE WHEN time_ended IS NULL THEN quantity ELSE 0 END), 0) AS active_portions,
        (SELECT COUNT(*) FROM Inventory_Alerts WHERE alert_type = 'low_stock' AND status = 'active') AS active_alerts
     FROM Kitchen"
)->fetch_assoc();

$recentConsumptions = $link->query(
    "SELECT ism.created_at, ii.ingredient_name, ism.quantity, uu.unit_symbol, ism.reference_id
     FROM Inventory_Stock_Movements ism
     INNER JOIN Inventory_Ingredients ii ON ii.ingredient_id = ism.ingredient_id
     INNER JOIN Inventory_Units uu ON uu.unit_id = ii.usage_unit_id
     WHERE ism.reference_type = 'kitchen' AND ism.movement_type = 'kitchen_usage'
     ORDER BY ism.created_at DESC
     LIMIT 8"
);

$alerts = $link->query(
    "SELECT ii.ingredient_name, ia.alert_message
     FROM Inventory_Alerts ia
     INNER JOIN Inventory_Ingredients ii ON ii.ingredient_id = ia.ingredient_id
     WHERE ia.alert_type = 'low_stock' AND ia.status = 'active'
     ORDER BY ia.created_at DESC
     LIMIT 6"
);
?>
<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php include '../inc/inventoryPanelStyles.php'; ?>

<meta http-equiv="refresh" content="8">

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="ops-head">
            <div>
                <h2 class="pull-left">Kitchen Production & Ingredient Control</h2>
                <p>Mark tickets complete, push ingredient consumption into stock history, and watch low-stock signals in the same kitchen workflow instead of treating inventory as a separate back-office task.</p>
            </div>
            <div>
                <a href="../posBackend/kitchenBackend/undo.php?UndoUnshow=true" class="btn btn-warning">Undo Last Completion</a>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo $_GET['status'] === 'error' ? 'danger' : 'success'; ?>">
                <?php echo htmlspecialchars($_GET['message'] ?? 'Kitchen workflow updated.'); ?>
            </div>
        <?php endif; ?>

        <div class="ops-grid">
            <div class="ops-card">
                <span>Active Tickets</span>
                <strong><?php echo (int) $kitchenSummary['active_tickets']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Portions In Queue</span>
                <strong><?php echo (int) $kitchenSummary['active_portions']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Low-Stock Alerts</span>
                <strong><?php echo (int) $kitchenSummary['active_alerts']; ?></strong>
            </div>
        </div>

        <div class="ops-panel">
            <h3>Kitchen Queue</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Kitchen ID</th>
                        <th>Table</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Submitted</th>
                        <th>Recipe Mapping</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kitchenOrders && $kitchenOrders->num_rows > 0): ?>
                        <?php while ($order = $kitchenOrders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo (int) $order['kitchen_id']; ?></td>
                                <td><?php echo (int) $order['table_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['item_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['item_id']); ?></small>
                                </td>
                                <td><?php echo (int) $order['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($order['time_submitted']); ?></td>
                                <td>
                                    <?php if ((int) $order['has_recipe'] === 1): ?>
                                        <span class="ops-badge good">Tracked</span>
                                    <?php else: ?>
                                        <span class="ops-badge pending">No recipe map</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../posBackend/kitchenBackend/kitchen-panel-back.php?action=set_time_ended&kitchen_id=<?php echo (int) $order['kitchen_id']; ?>" class="btn btn-dark btn-sm">Done</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No active kitchen tickets right now.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ops-panel" style="margin-top: 1.5rem;">
            <h3>Recent Ingredient Consumption</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Ingredient</th>
                        <th>Qty Used</th>
                        <th>Kitchen Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentConsumptions && $recentConsumptions->num_rows > 0): ?>
                        <?php while ($consumption = $recentConsumptions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($consumption['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($consumption['ingredient_name']); ?></td>
                                <td><?php echo number_format(abs((float) $consumption['quantity']), 3) . ' ' . htmlspecialchars($consumption['unit_symbol']); ?></td>
                                <td>#<?php echo (int) $consumption['reference_id']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No ingredient consumption has been logged yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ops-panel" style="margin-top: 1.5rem;">
            <h3>Kitchen Alert Feed</h3>
            <?php if ($alerts && $alerts->num_rows > 0): ?>
                <ul class="ops-list">
                    <?php while ($alert = $alerts->fetch_assoc()): ?>
                        <li><strong><?php echo htmlspecialchars($alert['ingredient_name']); ?>:</strong> <?php echo htmlspecialchars($alert['alert_message']); ?></li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="ops-empty">No low-stock issues are currently blocking kitchen production.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
