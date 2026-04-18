<?php
session_start();
require_once('../config.php');
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';
?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 760px;">
        <h2 class="pull-left">Check Staff Member Reservation Validity</h2>
        <form action="" method="post">
            <div class="form-group">
                <?php $currentStaffId = $_SESSION['logged_account_id'] ?? "Please Login"; ?>
                <label for="staffId">Staff ID:</label>
                <input type="text" id="staffId" name="staffId" class="form-control" value="<?= $currentStaffId ?>" readonly required>
            </div>
            <div class="form-group">
                <label for="memberId">Member ID:</label>
                <input type="text" id="memberId" name="memberId" class="form-control">
            </div>
            <div class="form-group">
                <label for="reservationId">Reservation ID:</label>
                <input type="text" id="reservationId" name="reservationId" class="form-control">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-dark">Check Validity</button>
                <a class="btn btn-light" href="javascript:window.history.back();">Cancel</a>
                <a class="btn btn-light" href="posTable.php">Tables Page</a>
            </div>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $staffId = $_POST['staffId'];
            $memberId = !empty($_POST['memberId']) ? $_POST['memberId'] : 1;
            $reservationId = !empty($_POST['reservationId']) ? $_POST['reservationId'] : 1111111;
            $bill_id = $_GET['bill_id'];

            $query = "SELECT * FROM Staffs WHERE staff_id = '$staffId'";
            $result = mysqli_query($link, $query);

            if (!$result) {
                echo '<div class="alert alert-warning mt-3">Error: ' . mysqli_error($link) . '</div>';
            } else {
                $staffExists = mysqli_num_rows($result) > 0;
                $memberExists = true;
                if (!empty($memberId)) {
                    $query = "SELECT * FROM Memberships WHERE member_id = '$memberId'";
                    $result = mysqli_query($link, $query);
                    $memberExists = $result ? mysqli_num_rows($result) > 0 : false;
                }

                $reservationExists = true;
                if (!empty($reservationId)) {
                    $query = "SELECT * FROM Reservations WHERE reservation_id = '$reservationId'";
                    $result = mysqli_query($link, $query);
                    $reservationExists = $result ? mysqli_num_rows($result) > 0 : false;
                }

                if ($staffExists && $memberExists && $reservationExists) {
                    echo '<div class="alert alert-success mt-3">Staff, member, and reservation are valid.</div>';
                    echo '<div style="display:flex; gap:0.75rem; flex-wrap:wrap;">';
                    echo '<a href="posCashPayment.php?bill_id=' . $bill_id . '&staff_id=' . $staffId . '&member_id=' . $memberId . '&reservation_id=' . $reservationId . '" class="btn btn-success">Cash</a>';
                    echo '<a href="posCardPayment.php?bill_id=' . $bill_id . '&staff_id=' . $staffId . '&member_id=' . $memberId . '&reservation_id=' . $reservationId . '" class="btn btn-primary">Credit Card</a>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-warning mt-3">Invalid staff, member, or reservation.</div>';
                }
            }
        }
        ?>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
