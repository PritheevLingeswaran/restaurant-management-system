<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$sql = "SELECT * FROM Accounts ORDER BY account_id;";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM Accounts WHERE email LIKE '%$escaped%' OR account_id LIKE '%$escaped%' ORDER BY account_id;";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Account Details</h2>
            <div>
                <a href="../staffCrud/createStaff.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Staff</a>
                <a href="../customerCrud/createCust.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Memberships</a>
            </div>
        </div>

        <form method="POST" action="#" class="legacy-search-row">
            <input type="text" id="search" name="search" class="form-control" placeholder="Enter Account ID, Email" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="account-panel.php" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Account ID</th>
                            <th>Email</th>
                            <th>Register Date</th>
                            <th>Phone Number</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $row['account_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['register_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['password']); ?></td>
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
