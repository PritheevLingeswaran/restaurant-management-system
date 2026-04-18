<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$search = trim($_POST['search'] ?? '');
$sql = "SELECT * FROM Menu ORDER BY item_id;";
if ($search !== '') {
    $escaped = mysqli_real_escape_string($link, $search);
    $sql = "SELECT * FROM Menu WHERE item_type LIKE '%$escaped%' OR item_category LIKE '%$escaped%' OR item_name LIKE '%$escaped%' OR item_id LIKE '%$escaped%' ORDER BY item_id;";
}
$result = mysqli_query($link, $sql);
?>

<div class="legacy-wrapper">
    <div class="legacy-surface">
        <div class="legacy-toolbar">
            <h2 class="pull-left">Items Details</h2>
            <a href="../menuCrud/createItem.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Item</a>
        </div>

        <form method="POST" action="#" class="legacy-search-row">
            <select name="search" id="search" class="form-control">
                <option value="">Select Item Type or Item Category</option>
                <?php
                $options = ['Main Dishes','Side Snacks','Drinks','Steak & Ribs','Seafood','Pasta','Lamb','Chicken','Burgers & Sandwiches','Bar Bites','House Dessert','Salad','Mathew Kid','Side Dishes','Classic Cocktails','Cold Pressed Juice','House Cocktails','Mocktails'];
                foreach ($options as $option):
                ?>
                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $search === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="menu-panel.php" class="btn btn-light">Show All</a>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="legacy-table-wrap">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['item_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_category']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_price']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                                <td><a href="../menuCrud/updateItemVerify.php?id=<?php echo urlencode($row['item_id']); ?>" title="Modify Record" data-toggle="tooltip" onclick="return confirm('Admin permission Required!\n\nAre you sure you want to Edit this Item?')"><i class="fa fa-pencil" aria-hidden="true"></i></a></td>
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
