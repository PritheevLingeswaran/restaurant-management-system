<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$currentDate = date('Y-m-d');
$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

$totalRevenueTodayQuery = "SELECT COALESCE(SUM(item_price * quantity), 0) AS total_revenue
                           FROM Bill_Items
                           INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                           INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                           WHERE DATE(Bills.bill_time) = '$currentDate'";
$totalRevenueToday = (float) mysqli_fetch_assoc(mysqli_query($link, $totalRevenueTodayQuery))['total_revenue'];

$totalRevenueThisWeekQuery = "SELECT COALESCE(SUM(item_price * quantity), 0) AS total_revenue
                              FROM Bill_Items
                              INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                              INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                              WHERE DATE(Bills.bill_time) >= '$currentWeekStart'";
$totalRevenueThisWeek = (float) mysqli_fetch_assoc(mysqli_query($link, $totalRevenueThisWeekQuery))['total_revenue'];

$totalRevenueThisMonthQuery = "SELECT COALESCE(SUM(item_price * quantity), 0) AS total_revenue
                               FROM Bill_Items
                               INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                               INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                               WHERE DATE(Bills.bill_time) >= '$currentMonthStart'";
$totalRevenueThisMonth = (float) mysqli_fetch_assoc(mysqli_query($link, $totalRevenueThisMonthQuery))['total_revenue'];

$totalRevenueQuery = "SELECT COALESCE(SUM(item_price * quantity), 0) AS total_revenue
                      FROM Bill_Items
                      INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id";
$totalRevenue = (float) mysqli_fetch_assoc(mysqli_query($link, $totalRevenueQuery))['total_revenue'];

$cardQuery = "
    SELECT IFNULL(SUM(bi.quantity * m.item_price), 0) AS card_revenue
    FROM Bills b
    LEFT JOIN Bill_Items bi ON b.bill_id = bi.bill_id
    LEFT JOIN Menu m ON bi.item_id = m.item_id
    WHERE LOWER(b.payment_method) = 'card'
      AND b.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
";
$cashQuery = "
    SELECT IFNULL(SUM(bi.quantity * m.item_price), 0) AS cash_revenue
    FROM Bills b
    LEFT JOIN Bill_Items bi ON b.bill_id = bi.bill_id
    LEFT JOIN Menu m ON bi.item_id = m.item_id
    WHERE LOWER(b.payment_method) = 'cash'
      AND b.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
";

$cardRevenue = (float) mysqli_fetch_assoc(mysqli_query($link, $cardQuery))['card_revenue'];
$cashRevenue = (float) mysqli_fetch_assoc(mysqli_query($link, $cashQuery))['cash_revenue'];
?>

<style>
    .stats-chart {
        width: 100%;
        min-height: 500px;
    }

    .stats-chart + .stats-chart {
        margin-top: 1.5rem;
    }
</style>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <div>
                <h2 class="mb-1">Revenue Statistics</h2>
                <p class="text-muted mb-0">Daily, weekly, monthly, and total revenue summary.</p>
            </div>
            <a href="../report/generate_report.php" class="btn btn-dark">Print Report</a>
        </div>

        <div class="legacy-table-wrap narrow-table">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Revenue Today</td>
                        <td><?php echo number_format($totalRevenueToday, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Revenue This Week</td>
                        <td><?php echo number_format($totalRevenueThisWeek, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Revenue This Month</td>
                        <td><?php echo number_format($totalRevenueThisMonth, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Revenue</td>
                        <td><?php echo number_format($totalRevenue, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="legacy-surface" style="margin-top: 1.5rem;">
        <div id="paymentMethodChart" class="stats-chart"></div>
        <div id="paymentMethodDonutChart" class="stats-chart"></div>
    </div>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
google.charts.load('current', { packages: ['corechart'] });
google.charts.setOnLoadCallback(paymentMethodCharts);

function paymentMethodCharts() {
  const barChartData = new google.visualization.DataTable();
  barChartData.addColumn('string', 'Payment Method');
  barChartData.addColumn('number', 'Revenue');
  barChartData.addRows([
    ['Card', <?php echo $cardRevenue; ?>],
    ['Cash', <?php echo $cashRevenue; ?>]
  ]);

  const donutChartData = new google.visualization.DataTable();
  donutChartData.addColumn('string', 'Payment Method');
  donutChartData.addColumn('number', 'Revenue');
  donutChartData.addRows([
    ['Card', <?php echo $cardRevenue; ?>],
    ['Cash', <?php echo $cashRevenue; ?>]
  ]);

  const barChartOptions = {
    title: 'Revenue Generated - <?php echo date('F Y'); ?>',
    bars: 'vertical'
  };

  const donutChartOptions = {
    title: 'Revenue Percentage - <?php echo date('F Y'); ?>',
    pieHole: 0.4
  };

  const barChart = new google.visualization.BarChart(document.getElementById('paymentMethodChart'));
  barChart.draw(barChartData, barChartOptions);

  const donutChart = new google.visualization.PieChart(document.getElementById('paymentMethodDonutChart'));
  donutChart.draw(donutChartData, donutChartOptions);
}
</script>

<?php
mysqli_close($link);
include '../inc/dashFooter.php';
?>
