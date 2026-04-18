<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';

$feedback = null;
$feedbackType = 'success';
$loggedStaffId = db_get_logged_staff_id($link);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_waste'])) {
    $ingredientId = trim($_POST['ingredient_id'] ?? '');
    $quantity = (float) ($_POST['quantity'] ?? 0);
    $reason = $_POST['waste_reason'] ?? 'waste';
    $notes = trim($_POST['notes'] ?? '');

    try {
        db_begin_transaction_with_isolation($link, 'READ COMMITTED');

        inventory_record_stock_movement(
            $link,
            $ingredientId,
            $reason === 'spoilage' ? 'spoilage' : 'waste',
            $quantity,
            0.0,
            'waste_log',
            null,
            $loggedStaffId,
            $notes !== '' ? $notes : 'Waste / spoilage logged from waste panel'
        );

        $insertWasteStmt = $link->prepare(
            "INSERT INTO Waste_Log (ingredient_id, quantity, waste_reason, notes, logged_at, logged_by)
             VALUES (?, ?, ?, ?, NOW(), ?)"
        );
        $insertWasteStmt->bind_param("sdssi", $ingredientId, $quantity, $reason, $notes, $loggedStaffId);
        $insertWasteStmt->execute();
        $insertWasteStmt->close();

        $link->commit();
        $feedback = 'Waste / spoilage entry logged successfully.';
    } catch (Throwable $exception) {
        $link->rollback();
        $feedback = $exception->getMessage();
        $feedbackType = 'danger';
    }
}

$wasteSummary = $link->query(
    "SELECT
        COALESCE(SUM(CASE WHEN waste_reason = 'waste' THEN quantity ELSE 0 END), 0) AS waste_qty,
        COALESCE(SUM(CASE WHEN waste_reason = 'spoilage' THEN quantity ELSE 0 END), 0) AS spoilage_qty,
        COUNT(*) AS records_count
     FROM Waste_Log
     WHERE DATE(logged_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
)->fetch_assoc();

$recentWaste = $link->query(
    "SELECT wl.logged_at, wl.quantity, wl.waste_reason, wl.notes, ii.ingredient_name, uu.unit_symbol
     FROM Waste_Log wl
     INNER JOIN Inventory_Ingredients ii ON ii.ingredient_id = wl.ingredient_id
     INNER JOIN Inventory_Units uu ON uu.unit_id = ii.usage_unit_id
     ORDER BY wl.logged_at DESC
     LIMIT 15"
);
?>
<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php include '../inc/inventoryPanelStyles.php'; ?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="ops-head">
            <div>
                <h2>Waste & Spoilage Tracking</h2>
                <p>Capture shrinkage, expiry, prep waste, and spoilage events so stock balances stay accurate and ingredient loss becomes visible to management instead of disappearing inside kitchen variance.</p>
            </div>
        </div>

        <?php if ($feedback !== null): ?>
            <div class="alert alert-<?php echo $feedbackType; ?>"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>

        <div class="ops-grid">
            <div class="ops-card">
                <span>30-Day Waste Qty</span>
                <strong><?php echo number_format((float) $wasteSummary['waste_qty'], 3); ?></strong>
            </div>
            <div class="ops-card">
                <span>30-Day Spoilage Qty</span>
                <strong><?php echo number_format((float) $wasteSummary['spoilage_qty'], 3); ?></strong>
            </div>
            <div class="ops-card">
                <span>Logged Events</span>
                <strong><?php echo (int) $wasteSummary['records_count']; ?></strong>
            </div>
        </div>

        <div class="ops-columns">
            <div class="ops-panel">
                <h3>Log Waste Event</h3>
                <form class="ops-form" method="post">
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
                        <label for="waste_reason">Reason</label>
                        <select id="waste_reason" name="waste_reason" required>
                            <option value="waste">Prep Waste</option>
                            <option value="spoilage">Spoilage / Expiry</option>
                        </select>
                    </div>
                    <div>
                        <label for="quantity">Quantity</label>
                        <input id="quantity" type="number" step="0.001" min="0.001" name="quantity" required>
                    </div>
                    <div class="full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Examples: trimmed fat loss, expired dairy batch, damaged produce crate"></textarea>
                    </div>
                    <div>
                        <button class="ops-button" type="submit" name="log_waste" value="1">Save Waste Log</button>
                    </div>
                </form>
            </div>

            <div class="ops-panel">
                <h3>Control Notes</h3>
                <ul class="ops-list">
                    <li>Prep waste and spoilage both reduce live stock immediately through the same movement engine used for kitchen consumption.</li>
                    <li>Separate reasons help you distinguish normal trim loss from avoidable expiry issues during review.</li>
                    <li>These logs support tighter cost-of-goods analysis because shrinkage stops hiding inside theoretical stock.</li>
                </ul>
            </div>
        </div>

        <div class="ops-panel">
            <h3>Recent Waste Register</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Ingredient</th>
                        <th>Reason</th>
                        <th>Quantity</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($waste = $recentWaste->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($waste['logged_at']); ?></td>
                            <td><?php echo htmlspecialchars($waste['ingredient_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($waste['waste_reason'])); ?></td>
                            <td><?php echo number_format((float) $waste['quantity'], 3) . ' ' . htmlspecialchars($waste['unit_symbol']); ?></td>
                            <td><?php echo htmlspecialchars($waste['notes']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
