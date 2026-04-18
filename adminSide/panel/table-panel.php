<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$sql = "SELECT * FROM Restaurant_Tables ORDER BY table_id;";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM Restaurant_Tables WHERE table_id LIKE '%$escaped%' OR capacity LIKE '%$escaped%' ORDER BY table_id;";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 980px;">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Table Details</h2>
            <a href="../tableCrud/createTable.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Table</a>
        </div>

        <form method="POST" action="#" class="legacy-search-row">
            <input type="text" id="search" name="search" class="form-control" placeholder="Enter Table ID, Capacity" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="table-panel.php" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap" style="max-width: 760px;">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Table ID</th>
                            <th>Capacity</th>
                            <th>Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $row['table_id']; ?></td>
                                <td><?php echo $row['capacity']; ?> Persons</td>
                                <td><?php echo $row['is_available'] ? 'Yes' : 'No'; ?></td>
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
