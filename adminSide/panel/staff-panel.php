<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$removeMode = isset($_GET['remove']) && $_GET['remove'] === '1';
$sql = "SELECT * FROM Staffs ORDER BY account_id";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM Staffs WHERE staff_name LIKE '%$escaped%' OR staff_id = '$escaped' ORDER BY account_id";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Staff Details</h2>
            <div class="d-flex gap-2 flex-wrap">
                <a href="../staffCrud/createStaff.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Staff</a>
                <a href="staff-panel.php?remove=1" class="btn btn-outline-dark"><i class="fa fa-trash"></i> Remove Staff</a>
            </div>
        </div>

        <form method="POST" action="<?php echo $removeMode ? 'staff-panel.php?remove=1' : '#'; ?>" class="legacy-search-row">
            <input type="text" id="search" name="search" class="form-control" placeholder="Enter Staff ID, Name" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="<?php echo $removeMode ? 'staff-panel.php?remove=1' : 'staff-panel.php'; ?>" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width:5em;">Staff ID</th>
                            <th>Staff Name</th>
                            <th style="width:7em;">Role</th>
                            <th>Account ID</th>
                            <?php if ($removeMode): ?>
                                <th style="width:6rem;">Delete</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $row['staff_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td><?php echo $row['account_id']; ?></td>
                                <?php if ($removeMode): ?>
                                    <td>
                                        <a href="../staffCrud/delete_staffVerify.php?id=<?php echo (int) $row['staff_id']; ?>" title="Delete Staff" data-toggle="tooltip" onclick="return confirm('Admin permission required!\\n\\nAre you sure you want to delete this staff record?')">
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
