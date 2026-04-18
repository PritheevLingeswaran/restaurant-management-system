<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$sql = "SELECT * FROM Bills ORDER BY bill_id;";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM Bills WHERE table_id LIKE '%$escaped%' OR payment_method LIKE '%$escaped%' OR bill_id LIKE '%$escaped%' OR card_id LIKE '%$escaped%'";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Search Bills Details</h2>
        </div>

        <form method="POST" action="#" class="legacy-search-row">
            <input type="text" id="search" name="search" class="form-control" placeholder="Enter Bill ID, Table ID, Card ID, Payment Method" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="bill-panel.php" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap narrow-table">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 5.5rem;">Bill ID</th>
                            <th style="width: 5.5rem;">Staff ID</th>
                            <th style="width: 6rem;">Member ID</th>
                            <th style="width: 7rem;">Reservation ID</th>
                            <th style="width: 5rem;">Table ID</th>
                            <th style="width: 5rem;">Card ID</th>
                            <th>Payment Method</th>
                            <th style="width:10rem">Bill Time</th>
                            <th style="width:10rem">Payment Time</th>
                            <th style="width:5rem;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $row['bill_id']; ?></td>
                                <td><?php echo $row['staff_id']; ?></td>
                                <td><?php echo $row['member_id']; ?></td>
                                <td><?php echo $row['reservation_id']; ?></td>
                                <td><?php echo $row['table_id']; ?></td>
                                <td><?php echo $row['card_id']; ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['payment_method'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['bill_time'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['payment_time'] ?? '-')); ?></td>
                                <td><a href="../posBackend/receipt.php?bill_id=<?php echo (int) $row['bill_id']; ?>" title="Receipt" data-toggle="tooltip"><span class="fa fa-receipt text-black"></span></a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-danger"><em>No records were found.</em></div>
        <?php endif; ?>
    </div>
</div>

<?php
if ($result) {
    mysqli_free_result($result);
}
mysqli_close($link);
include '../inc/dashFooter.php';
?>
