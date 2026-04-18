<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$currentMonth = date('Y-m');
$sortOrder = (isset($_GET['sortOrder']) && strtolower($_GET['sortOrder']) === 'asc') ? 'ASC' : 'DESC';

$menuItemSalesQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
                       FROM Bill_Items
                       INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                       INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                       WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                       GROUP BY Menu.item_name
                       ORDER BY total_quantity $sortOrder";
$menuItemSalesResult = mysqli_query($link, $menuItemSalesQuery);
?>

<style>
    .sales-table {
        width: 100%;
        margin-bottom: 0;
    }

    .sales-table th:last-child,
    .sales-table td:last-child {
        width: 140px;
        text-align: left;
        white-space: nowrap;
    }

    .sales-chart {
        width: 100%;
        min-height: 500px;
    }

    .sales-chart + .sales-chart {
        margin-top: 1.5rem;
    }
</style>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <div>
                <h2 class="mb-1">Most Purchased Items</h2>
                <p class="text-muted mb-0">(<?php echo htmlspecialchars($currentMonth); ?>)</p>
            </div>
        </div>

        <div class="legacy-table-wrap narrow-table">
            <table class="table table-bordered table-striped sales-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Units</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($menuItemSalesResult && mysqli_num_rows($menuItemSalesResult) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($menuItemSalesResult)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo (int) $row['total_quantity']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">No sales found for this month.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="legacy-surface" style="margin-top: 1.5rem;">
        <div id="mostPurchased" class="sales-chart"></div>
        <div id="mostPurchasedMain" class="sales-chart"></div>
        <div id="mostPurchasedDrinks" class="sales-chart"></div>
        <div id="mostPurchasedSide" class="sales-chart"></div>
    </div>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(mostPurchasedChart);
    google.charts.setOnLoadCallback(mostPurchasedDrinksChart);
    google.charts.setOnLoadCallback(mostPurchasedMainChart);
    google.charts.setOnLoadCallback(mostPurchasedSideChart);

    function mostPurchasedChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedItemsQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
                                       FROM Bill_Items
                                       INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                                       INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                                       WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                       GROUP BY Menu.item_name
                                       ORDER BY total_quantity DESC
                                       LIMIT 10";
            $topPurchasedItemsResult = mysqli_query($link, $topPurchasedItemsQuery);

            while ($row = mysqli_fetch_assoc($topPurchasedItemsResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20,
                bold: true
            },
            title: 'Top 10 Most Purchased Items - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchased'));
        chart.draw(data, options);
    }

    function mostPurchasedDrinksChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedDrinksQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
                                        FROM Bill_Items
                                        INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                                        INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                                        WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                          AND Menu.item_category = 'Drinks'
                                        GROUP BY Menu.item_name
                                        ORDER BY total_quantity DESC
                                        LIMIT 10";
            $topPurchasedDrinksResult = mysqli_query($link, $topPurchasedDrinksQuery);

            while ($row = mysqli_fetch_assoc($topPurchasedDrinksResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20,
                bold: true
            },
            title: 'Top 10 Most Purchased Drinks - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchasedDrinks'));
        chart.draw(data, options);
    }

    function mostPurchasedMainChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedMainDishesQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
                                            FROM Bill_Items
                                            INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                                            INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                                            WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                              AND Menu.item_category = 'Main Dishes'
                                            GROUP BY Menu.item_name
                                            ORDER BY total_quantity DESC
                                            LIMIT 10";
            $topPurchasedMainDishesResult = mysqli_query($link, $topPurchasedMainDishesQuery);

            while ($row = mysqli_fetch_assoc($topPurchasedMainDishesResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20,
                bold: true
            },
            title: 'Top 10 Most Purchased Main Dishes - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchasedMain'));
        chart.draw(data, options);
    }

    function mostPurchasedSideChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedSideSnacksQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
                                            FROM Bill_Items
                                            INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                                            INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                                            WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                              AND Menu.item_category = 'Side Snacks'
                                            GROUP BY Menu.item_name
                                            ORDER BY total_quantity DESC
                                            LIMIT 10";
            $topPurchasedSideSnacksResult = mysqli_query($link, $topPurchasedSideSnacksQuery);

            while ($row = mysqli_fetch_assoc($topPurchasedSideSnacksResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20,
                bold: true
            },
            title: 'Top 10 Most Purchased Side Snacks - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchasedSide'));
        chart.draw(data, options);
    }
</script>

<?php
if ($menuItemSalesResult) {
    mysqli_free_result($menuItemSalesResult);
}
mysqli_close($link);
include '../inc/dashFooter.php';
?>
