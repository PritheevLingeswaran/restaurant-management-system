<?php
session_start();
require_once '../config.php';
include '../inc/dashHeader.php';
include '../inc/legacyPanelLayout.php';

$item_id = $item_name = $item_type = $item_category = $item_price = $item_description = "";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $item_id = $_GET['id'];
    $sql = "SELECT * FROM Menu WHERE item_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_item_id);
        $param_item_id = $item_id;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $item_name = $row['item_name'];
                $item_type = $row['item_type'];
                $item_category = $row['item_category'];
                $item_price = $row['item_price'];
                $item_description = $row['item_description'];
            } else {
                exit('Item not found.');
            }
        } else {
            exit('Error retrieving item details.');
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = trim($_POST["item_name"]);
    $item_type = trim($_POST["item_type"]);
    $item_category = trim($_POST["item_category"]);
    $item_price = floatval($_POST["item_price"]);
    $item_description = $_POST["item_description"];

    $update_sql = "UPDATE Menu SET item_name='$item_name', item_type='$item_type', item_category='$item_category', item_price='$item_price', item_description='$item_description' WHERE item_id='$item_id'";
    $resultItems = mysqli_query($link, $update_sql);
    if ($resultItems) {
        header("Location: ../panel/menu-panel.php");
        exit();
    }
}
?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 860px;">
        <h2 class="pull-left">Update Item</h2>
        <p>Edit the menu item details below.</p>
        <form action="" method="post">
            <div class="form-group">
                <label for="item_name" class="form-label">Item Name:</label>
                <input type="text" name="item_name" id="item_name" class="form-control" placeholder="Spaghetti" value="<?php echo htmlspecialchars($item_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="item_type" class="form-label">Item Type:</label>
                <input type="text" name="item_type" id="item_type" class="form-control" placeholder="Pasta" value="<?php echo htmlspecialchars($item_type); ?>" required>
            </div>
            <div class="form-group">
                <label for="item_category">Item Category:</label>
                <input type="text" name="item_category" id="item_category" class="form-control" placeholder="Main Dishes" value="<?php echo htmlspecialchars($item_category); ?>" required>
            </div>
            <div class="form-group">
                <label for="item_price">Item Price:</label>
                <input type="number" min="0.01" step="0.01" name="item_price" id="item_price" placeholder="Enter Item Price" class="form-control" value="<?php echo htmlspecialchars($item_price);?>" required>
            </div>
            <div class="form-group">
                <label for="item_description" class="form-label">Item Description:</label>
                <textarea name="item_description" id="item_description" placeholder="The dish...." required class="form-control"><?php echo htmlspecialchars($item_description); ?></textarea>
            </div>
            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
            <button class="btn btn-dark" type="submit" name="submit" value="submit">Update</button>
            <a class="btn btn-light" href="../panel/menu-panel.php">Cancel</a>
        </form>
    </div>
</div>
