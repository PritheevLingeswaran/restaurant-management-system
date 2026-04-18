<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';

$supplierSummary = $link->query(
    "SELECT
        COUNT(*) AS supplier_count,
        COALESCE(AVG(on_time_score), 0) AS avg_score,
        COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_suppliers
     FROM Inventory_Suppliers"
)->fetch_assoc();

$suppliers = $link->query(
    "SELECT supplier_id, supplier_name, contact_person, phone, email, lead_time_days, on_time_score, is_active
     FROM Inventory_Suppliers
     ORDER BY is_active DESC, supplier_name"
);

$supplierItems = $link->query(
    "SELECT s.supplier_name, i.ingredient_name, si.supplier_price, uu.unit_symbol, si.is_preferred
     FROM Inventory_Supplier_Items si
     INNER JOIN Inventory_Suppliers s ON s.supplier_id = si.supplier_id
     INNER JOIN Inventory_Ingredients i ON i.ingredient_id = si.ingredient_id
     INNER JOIN Inventory_Units uu ON uu.unit_id = i.purchase_unit_id
     ORDER BY s.supplier_name, i.ingredient_name"
);
?>
<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php include '../inc/inventoryPanelStyles.php'; ?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="ops-head">
            <div>
                <h2>Supplier Management</h2>
                <p>Manage vendors for vegetables, meat, dairy, seafood, bakery, and beverage sourcing with lead time, contact details, and preferred item mappings for procurement decisions.</p>
            </div>
        </div>

        <div class="ops-grid">
            <div class="ops-card">
                <span>Total Suppliers</span>
                <strong><?php echo (int) $supplierSummary['supplier_count']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Active Suppliers</span>
                <strong><?php echo (int) $supplierSummary['active_suppliers']; ?></strong>
            </div>
            <div class="ops-card">
                <span>Average Reliability</span>
                <strong><?php echo number_format((float) $supplierSummary['avg_score'], 1); ?>/10</strong>
            </div>
        </div>

        <div class="ops-panel">
            <h3>Supplier Directory</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Lead Time</th>
                        <th>Reliability</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong><br>
                                <small>ID <?php echo (int) $supplier['supplier_id']; ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($supplier['contact_person']); ?><br>
                                <small><?php echo htmlspecialchars($supplier['phone']); ?> · <?php echo htmlspecialchars($supplier['email']); ?></small>
                            </td>
                            <td><?php echo (int) $supplier['lead_time_days']; ?> days</td>
                            <td><?php echo number_format((float) $supplier['on_time_score'], 1); ?>/10</td>
                            <td>
                                <?php if ((int) $supplier['is_active'] === 1): ?>
                                    <span class="ops-badge good">Active</span>
                                <?php else: ?>
                                    <span class="ops-badge pending">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="ops-panel" style="margin-top: 1.5rem;">
            <h3>Preferred Supply Mapping</h3>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Ingredient</th>
                        <th>Rate</th>
                        <th>Flag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($supplierItem = $supplierItems->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplierItem['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($supplierItem['ingredient_name']); ?></td>
                            <td>Rs <?php echo number_format((float) $supplierItem['supplier_price'], 2) . ' / ' . htmlspecialchars($supplierItem['unit_symbol']); ?></td>
                            <td>
                                <?php if ((int) $supplierItem['is_preferred'] === 1): ?>
                                    <span class="ops-badge good">Preferred</span>
                                <?php else: ?>
                                    <span class="ops-badge pending">Alternate</span>
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
