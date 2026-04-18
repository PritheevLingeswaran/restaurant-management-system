<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$memberId = isset($_GET['member_id']) ? (int) $_GET['member_id'] : 1;

$mostOrderedItemsQuery = "SELECT Menu.item_name, SUM(Bill_Items.quantity) AS order_count
                          FROM Bill_Items
                          INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                          INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                          WHERE Bills.member_id = $memberId
                          GROUP BY Bill_Items.item_id, Menu.item_name
                          ORDER BY order_count DESC";
$mostOrderedItemsResult = mysqli_query($link, $mostOrderedItemsQuery);

$chartLabels = [];
$chartData = [];
if ($mostOrderedItemsResult && mysqli_num_rows($mostOrderedItemsResult) > 0) {
    mysqli_data_seek($mostOrderedItemsResult, 0);
    $itemCount = 0;
    while ($row = mysqli_fetch_assoc($mostOrderedItemsResult)) {
        if ($itemCount >= 5) {
            break;
        }
        $chartLabels[] = $row['item_name'];
        $chartData[] = (int) $row['order_count'];
        $itemCount++;
    }
    mysqli_data_seek($mostOrderedItemsResult, 0);
}
?>

<style>
    .profile-chart-wrap {
        width: 100%;
        max-width: 820px;
        height: 520px;
        margin-top: 1rem;
    }
</style>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Member Profiles</h2>
        </div>

        <form method="get" action="#" class="legacy-search-row">
            <input required type="text" id="member_id" name="member_id" class="form-control" placeholder="Enter Member ID" value="<?php echo htmlspecialchars((string) $memberId); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <div></div>
        </form>

        <?php if (!$mostOrderedItemsResult || mysqli_num_rows($mostOrderedItemsResult) === 0): ?>
            <div class="alert alert-danger mb-0">Member ID not found.</div>
        <?php else: ?>
            <div class="legacy-toolbar" style="margin-top: 1rem;">
                <div>
                    <h3 class="h4 mb-1">Showing Member ID - <?php echo $memberId; ?></h3>
                    <p class="text-muted mb-0">Most Ordered Items - (All Time)</p>
                </div>
            </div>

            <div class="legacy-table-wrap narrow-table">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($mostOrderedItemsResult)) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo (int) $row['order_count']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($chartLabels)): ?>
        <div class="legacy-surface" style="margin-top: 1.5rem;">
            <h3 class="h4 mb-3">Top 5 Favourites - (All Time)</h3>
            <div class="profile-chart-wrap">
                <canvas id="mostOrderedItemsChart"></canvas>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($chartLabels)): ?>
    const ctx = document.getElementById('mostOrderedItemsChart');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($chartData); ?>,
                backgroundColor: [
                    'rgb(8, 32, 50)',
                    'rgb(255, 76, 41)',
                    'rgb(13, 18, 130)',
                    'rgb(143, 67, 238)',
                    'rgb(179, 19, 18)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right'
                }
            }
        }
    });
<?php endif; ?>
</script>

<?php
if ($mostOrderedItemsResult) {
    mysqli_free_result($mostOrderedItemsResult);
}
mysqli_close($link);
include '../inc/dashFooter.php';
?>
