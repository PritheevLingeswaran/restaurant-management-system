<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$removeMode = isset($_GET['remove']) && $_GET['remove'] === '1';
$sql = "SELECT * FROM Memberships ORDER BY member_id";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM Memberships WHERE member_name LIKE '%$escaped%' OR member_id = '$escaped' ORDER BY member_id";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Membership Details</h2>
            <div class="d-flex gap-2 flex-wrap">
                <a href="../customerCrud/createCust.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Membership</a>
                <a href="customer-panel.php?remove=1" class="btn btn-outline-dark"><i class="fa fa-trash"></i> Remove Membership</a>
            </div>
        </div>

        <form method="POST" action="<?php echo $removeMode ? 'customer-panel.php?remove=1' : '#'; ?>" class="legacy-search-row">
            <input type="text" id="search" name="search" class="form-control" placeholder="Enter Member ID, Name" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="<?php echo $removeMode ? 'customer-panel.php?remove=1' : 'customer-panel.php'; ?>" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap" style="max-width: 980px;">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width:7em;">Member Id</th>
                            <th style="width: 22rem;">Member Name</th>
                            <th style="width:6.5rem;">Points</th>
                            <th style="width:8rem;">Account ID</th>
                            <?php if ($removeMode): ?>
                                <th style="width:6rem;">Delete</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $row['member_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                                <td><?php echo $row['points']; ?></td>
                                <td><?php echo $row['account_id']; ?></td>
                                <?php if ($removeMode): ?>
                                    <td>
                                        <a href="../customerCrud/deleteCustomerVerify.php?id=<?php echo (int) $row['member_id']; ?>" title="Delete Member" data-toggle="tooltip" onclick="return confirm('Admin permission required!\\n\\nAre you sure you want to delete this membership?')">
                                            <span class="fa fa-trash text-black"></span>
                                        </a>
                                    </td>
                                <?php endif; ?>
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
