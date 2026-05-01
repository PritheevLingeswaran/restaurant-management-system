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

$latestBillMonthQuery = "SELECT DATE_FORMAT(MAX(bill_time), '%Y-%m') AS latest_month FROM Bills";
$latestBillMonthResult = mysqli_query($link, $latestBillMonthQuery);
$latestBillMonthRow = $latestBillMonthResult ? mysqli_fetch_assoc($latestBillMonthResult) : null;
$reportingMonth = $currentMonth;

if (!empty($latestBillMonthRow['latest_month'])) {
    $currentMonthCountQuery = "SELECT COUNT(*) AS bill_count
                               FROM Bills
                               WHERE bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'";
    $currentMonthCountResult = mysqli_query($link, $currentMonthCountQuery);
    $currentMonthCountRow = $currentMonthCountResult ? mysqli_fetch_assoc($currentMonthCountResult) : ['bill_count' => 0];

    if ((int) $currentMonthCountRow['bill_count'] === 0) {
        $reportingMonth = $latestBillMonthRow['latest_month'];
    }

    if ($currentMonthCountResult) {
        mysqli_free_result($currentMonthCountResult);
    }
}

$reportingMonthStart = date('Y-m-01', strtotime($reportingMonth . '-01'));
$reportingMonthEnd = date('Y-m-t', strtotime($reportingMonth . '-01'));
$reportingMonthLabel = date('F Y', strtotime($reportingMonth . '-01'));
$isFallbackMonth = $reportingMonth !== $currentMonth;

function fetchSalesChartRows(mysqli $link, string $monthStart, string $monthEnd, ?string $category = null): array
{
    $categoryFilter = $category !== null ? " AND Menu.item_category = ?" : "";
    $query = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
              FROM Bill_Items
              INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
              INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
              WHERE Bills.bill_time BETWEEN ? AND ?" . $categoryFilter . "
              GROUP BY Menu.item_name
              ORDER BY total_quantity DESC
              LIMIT 10";
    $stmt = $link->prepare($query);
    $rangeStart = $monthStart . ' 00:00:00';
    $rangeEnd = $monthEnd . ' 23:59:59';

    if ($category !== null) {
        $stmt->bind_param('sss', $rangeStart, $rangeEnd, $category);
    } else {
        $stmt->bind_param('ss', $rangeStart, $rangeEnd);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = [$row['item_name'], (int) $row['total_quantity']];
    }

    $stmt->close();

    return $rows;
}

$menuItemSalesQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS total_quantity
                       FROM Bill_Items
                       INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                       INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                       WHERE Bills.bill_time BETWEEN '$reportingMonthStart 00:00:00' AND '$reportingMonthEnd 23:59:59'
                       GROUP BY Menu.item_name
                       ORDER BY total_quantity $sortOrder";
$menuItemSalesResult = mysqli_query($link, $menuItemSalesQuery);

$allItemsChartRows = fetchSalesChartRows($link, $reportingMonthStart, $reportingMonthEnd);
$mainItemsChartRows = fetchSalesChartRows($link, $reportingMonthStart, $reportingMonthEnd, 'Main Dishes');
$drinksChartRows = fetchSalesChartRows($link, $reportingMonthStart, $reportingMonthEnd, 'Drinks');
$sideItemsChartRows = fetchSalesChartRows($link, $reportingMonthStart, $reportingMonthEnd, 'Side Snacks');
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
                <p class="text-muted mb-0">(<?php echo htmlspecialchars($reportingMonth); ?>)</p>
                <?php if ($isFallbackMonth): ?>
                    <p class="text-muted mb-0">Showing the latest month with sales data because the current month has no bills yet.</p>
                <?php endif; ?>
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

    const reportingMonthLabel = <?php echo json_encode($reportingMonthLabel); ?>;
    const allItemsChartRows = <?php echo json_encode($allItemsChartRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const drinksChartRows = <?php echo json_encode($drinksChartRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const mainItemsChartRows = <?php echo json_encode($mainItemsChartRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const sideItemsChartRows = <?php echo json_encode($sideItemsChartRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    function drawPieChart(elementId, title, rows) {
        const chartHost = document.getElementById(elementId);

        if (!rows.length) {
            chartHost.innerHTML = '<div class="alert alert-light border mb-0">No sales data available for this chart.</div>';
            return;
        }

        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            ...rows
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20,
                bold: true
            },
            title,
            is3D: true
        };

        const chart = new google.visualization.PieChart(chartHost);
        chart.draw(data, options);
    }

    function mostPurchasedChart() {
        drawPieChart('mostPurchased', `Top 10 Most Purchased Items - ${reportingMonthLabel}`, allItemsChartRows);
    }

    function mostPurchasedDrinksChart() {
        drawPieChart('mostPurchasedDrinks', `Top 10 Most Purchased Drinks - ${reportingMonthLabel}`, drinksChartRows);
    }

    function mostPurchasedMainChart() {
        drawPieChart('mostPurchasedMain', `Top 10 Most Purchased Main Dishes - ${reportingMonthLabel}`, mainItemsChartRows);
    }

    function mostPurchasedSideChart() {
        drawPieChart('mostPurchasedSide', `Top 10 Most Purchased Side Snacks - ${reportingMonthLabel}`, sideItemsChartRows);
    }
</script>

<?php
if ($menuItemSalesResult) {
    mysqli_free_result($menuItemSalesResult);
}
mysqli_close($link);
include '../inc/dashFooter.php';
?>
