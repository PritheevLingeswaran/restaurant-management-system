<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';

$feedback = null;
$feedbackType = 'success';
$loggedStaffId = db_get_logged_staff_id($link);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_adjust'])) {
    $ingredientId = trim($_POST['ingredient_id'] ?? '');
    $quantity = (float) ($_POST['quantity'] ?? 0);
    $movementType = $_POST['movement_type'] ?? 'stock_in';
    $notes = trim($_POST['notes'] ?? '');

    try {
        db_begin_transaction_with_isolation($link, 'READ COMMITTED');
        inventory_record_stock_movement(
            $link,
            $ingredientId,
            $movementType,
            $quantity,
            0.0,
            'inventory_panel',
            null,
            $loggedStaffId,
            $notes !== '' ? $notes : 'Manual stock adjustment from inventory panel'
        );
        $link->commit();
        $feedback = 'Inventory movement saved successfully.';
    } catch (Throwable $exception) {
        $link->rollback();
        $feedback = $exception->getMessage();
        $feedbackType = 'danger';
    }
}

$summary = [
    'ingredients' => 0,
    'low_stock' => 0,
    'stock_value' => 0,
    'movements_today' => 0,
];

$summaryQuery = "
    SELECT
        (SELECT COUNT(*) FROM Inventory_Ingredients WHERE is_active = 1) AS ingredients_count,
        (SELECT COUNT(*) FROM Inventory_Alerts WHERE alert_type = 'low_stock' AND status = 'active') AS low_stock_count,
        (SELECT COALESCE(SUM(current_stock * average_unit_cost), 0) FROM Inventory_Ingredients WHERE is_active = 1) AS stock_value_total,
        (SELECT COUNT(*) FROM Inventory_Stock_Movements WHERE DATE(created_at) = CURDATE()) AS movement_count_today
";
$summaryResult = $link->query($summaryQuery);
if ($summaryRow = $summaryResult->fetch_assoc()) {
    $summary['ingredients'] = (int) $summaryRow['ingredients_count'];
    $summary['low_stock'] = (int) $summaryRow['low_stock_count'];
    $summary['stock_value'] = (float) $summaryRow['stock_value_total'];
    $summary['movements_today'] = (int) $summaryRow['movement_count_today'];
}

$ingredients = $link->query(
    "SELECT ii.ingredient_id, ii.ingredient_name, ic.category_name, ii.current_stock, ii.reorder_level, ii.par_level,
            uu.unit_symbol, ii.average_unit_cost, ii.storage_area,
            CASE WHEN ii.current_stock <= ii.reorder_level THEN 1 ELSE 0 END AS is_low_stock
     FROM Inventory_Ingredients ii
     INNER JOIN Inventory_Categories ic ON ic.category_id = ii.category_id
     INNER JOIN Inventory_Units uu ON uu.unit_id = ii.usage_unit_id
     WHERE ii.is_active = 1
     ORDER BY is_low_stock DESC, ic.category_name, ii.ingredient_name"
);

$alerts = $link->query(
    "SELECT ia.alert_message, ii.ingredient_name
     FROM Inventory_Alerts ia
     INNER JOIN Inventory_Ingredients ii ON ii.ingredient_id = ia.ingredient_id
     WHERE ia.alert_type = 'low_stock' AND ia.status = 'active'
     ORDER BY ia.created_at DESC
     LIMIT 8"
);

$recentMovements = $link->query(
    "SELECT ism.created_at, ism.movement_type, ism.quantity, ii.ingredient_name, uu.unit_symbol, ism.notes
     FROM Inventory_Stock_Movements ism
     INNER JOIN Inventory_Ingredients ii ON ii.ingredient_id = ism.ingredient_id
     INNER JOIN Inventory_Units uu ON uu.unit_id = ii.usage_unit_id
     ORDER BY ism.created_at DESC
     LIMIT 10"
);
?>
<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php include '../inc/inventoryPanelStyles.php'; ?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="ops-head">
            <div>
                <h2>Advanced Inventory Control</h2>
                <p>Track vegetables, meat, dairy, dry goods, beverages, and all supporting ingredients with current stock, reorder levels, cost value, and movement history from one staff-facing workspace.</p>
            </div>
        </div>

        <?php if ($feedback !== null): ?>
            <div class="alert alert-<?php echo $feedbackType; ?>"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>

        <div class="ops-grid">
            <div class="ops-card">
                <span>Active Ingredients</span>
                <strong><?php echo $summary['ingredients']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Low-Stock Alerts</span>
                <strong><?php echo $summary['low_stock']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Inventory Value</span>
                <strong>Rs <?php echo number_format($summary['stock_value'], 2); ?></strong>
            </div>
            <div class="ops-card">
                <span>Today&apos;s Movements</span>
                <strong><?php echo $summary['movements_today']; ?></strong>
            </div>
        </div>

        <div class="ops-columns">
            <div class="ops-panel">
                <h3>Manual Stock In / Stock Out</h3>
                <form class="ops-form" method="post">
                    <div>
                        <label for="ingredient_id">Ingredient</label>
                        <select id="ingredient_id" name="ingredient_id" required>
                            <option value="">Select ingredient</option>
                            <?php
                            $ingredientOptions = $link->query("SELECT ingredient_id, ingredient_name FROM Inventory_Ingredients WHERE is_active = 1 ORDER BY ingredient_name");
                            while ($option = $ingredientOptions->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($option['ingredient_id']); ?>">
                                    <?php echo htmlspecialchars($option['ingredient_name'] . ' (' . $option['ingredient_id'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="movement_type">Movement Type</label>
                        <select id="movement_type" name="movement_type" required>
                            <option value="stock_in">Stock In</option>
                            <option value="stock_out">Stock Out</option>
                            <option value="adjustment_in">Adjustment In</option>
                            <option value="adjustment_out">Adjustment Out</option>
                        </select>
                    </div>
                    <div>
                        <label for="quantity">Quantity</label>
                        <input id="quantity" type="number" step="0.001" min="0.001" name="quantity" required>
                    </div>
                    <div class="full">
                        <label for="notes">Reason / Notes</label>
                        <textarea id="notes" name="notes" placeholder="Examples: morning receiving adjustment, shrinkage correction, bulk store transfer"></textarea>
                    </div>
                    <div>
                        <button class="ops-button" type="submit" name="inventory_adjust" value="1">Save Movement</button>
                    </div>
                </form>
            </div>

            <div class="ops-panel">
                <h3>Active Low-Stock Alerts</h3>
                <?php if ($alerts && $alerts->num_rows > 0): ?>
                    <ul class="ops-list">
                        <?php while ($alert = $alerts->fetch_assoc()): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($alert['ingredient_name']); ?>:</strong>
                                <?php echo htmlspecialchars($alert['alert_message']); ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="ops-empty">No active low-stock alerts right now. Inventory levels are above their reorder thresholds.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="ops-panel">
            <h3>Ingredient Balances</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Par / Reorder</th>
                        <th>Avg Cost</th>
                        <th>Stock Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($ingredient = $ingredients->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ingredient['ingredient_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($ingredient['ingredient_id']); ?> · <?php echo htmlspecialchars($ingredient['storage_area']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($ingredient['category_name']); ?></td>
                            <td><?php echo number_format((float) $ingredient['current_stock'], 3) . ' ' . htmlspecialchars($ingredient['unit_symbol']); ?></td>
                            <td>
                                Par <?php echo number_format((float) $ingredient['par_level'], 3); ?><br>
                                Reorder <?php echo number_format((float) $ingredient['reorder_level'], 3); ?>
                            </td>
                            <td>Rs <?php echo number_format((float) $ingredient['average_unit_cost'], 2); ?></td>
                            <td>Rs <?php echo number_format((float) $ingredient['current_stock'] * (float) $ingredient['average_unit_cost'], 2); ?></td>
                            <td>
                                <?php if ((int) $ingredient['is_low_stock'] === 1): ?>
                                    <span class="ops-badge low">Low stock</span>
                                <?php else: ?>
                                    <span class="ops-badge good">Healthy</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="ops-panel" style="margin-top: 1.5rem;">
            <h3>Recent Stock Movements</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Ingredient</th>
                        <th>Type</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($movement = $recentMovements->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($movement['created_at']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($movement['ingredient_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($movement['notes']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $movement['movement_type']))); ?></td>
                            <td><?php echo number_format((float) $movement['quantity'], 3) . ' ' . htmlspecialchars($movement['unit_symbol']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
