<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$sql = "SELECT * FROM reservations ORDER BY reservation_date DESC, reservation_time DESC;";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM reservations WHERE reservation_date LIKE '%$escaped%' OR reservation_id LIKE '%$escaped%' OR customer_name LIKE '%$escaped%' ORDER BY reservation_date DESC, reservation_time DESC;";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Reservation Details</h2>
            <a href="../reservationsCrud/createReservation.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Reservation</a>
        </div>

        <form method="POST" action="#" class="legacy-search-row">
            <input type="text" id="search" name="search" class="form-control" placeholder="Enter Reservation ID, Customer Name, Reservation Date (2026-04)" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="reservation-panel.php" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Customer Name</th>
                            <th>Table ID</th>
                            <th>Reservation Time</th>
                            <th>Reservation Date</th>
                            <th>Head Count</th>
                            <th>Special Request</th>
                            <th>Delete</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $row['reservation_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo $row['table_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['reservation_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                                <td><?php echo $row['head_count']; ?></td>
                                <td><?php echo htmlspecialchars($row['special_request']); ?></td>
                                <td><a href="../reservationsCrud/deleteReservationVerify.php?id=<?php echo (int) $row['reservation_id']; ?>" title="Delete Record" data-toggle="tooltip" onclick="return confirm('Admin permission Required!\n\nAre you sure you want to delete this Reservation?\n\nThis will alter other modules related to this Reservation!\n')"><span class="fa fa-trash text-black"></span></a></td>
                                <td><a href="../reservationsCrud/reservationReceipt.php?reservation_id=<?php echo (int) $row['reservation_id']; ?>" title="Receipt" data-toggle="tooltip"><span class="fa fa-receipt text-black"></span></a></td>
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
