<?php
session_start(); // Ensure session is started
?>
<?php  include '../inc/dashHeader.php'?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php
// Include config file
require_once "../config.php";

$conn = $link;

 
$input_table_id = $table_id_err = $table_id = "";

// Function to get the next available table id
function getNextAvailableTableID($conn) {
    $sql = "SELECT MAX(table_id) as max_table_id FROM Restaurant_Tables";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $next_table_id = $row['max_table_id'] + 1;
    return $next_table_id;
}

// Get the next available table id
$next_table_id = getNextAvailableTableID($conn);

?>
<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 760px;">
    <h2 class="pull-left">Create New Table</h2>
    <p>Please fill in the Table Information</p>
    
<form method="POST" action="succ_create_table.php">
    
        <div class="form-group">
            <label for="table_id" class="form-label">Table ID :</label>
            <input min="1" type="number" name="table_id" placeholder="1" class="form-control <?php echo $next_tab_idle ? 'is-invalid' : ''; ?>" id="next_tab_idle" required value="<?php echo $next_table_id; ?>" readonly><br>
            <div id="validationServerFeedback" class="invalid-feedback">
            Please provide a valid table id.
            </div>
        </div>
    
        <div class="form-group"> 
            <label for="capacity">Capacity :</label>
            <input placeholder="8" type="number" name="capacity" min=1 id="capacity" required class="form-control <?php echo (!empty($capacity)) ? 'is-invalid' : ''; ?>" ><br>
            <span class="invalid-feedback"></span>
        </div>

        
        
        <div class="form-group">
            <input type="submit" class="btn btn-dark" value="Create table">
        </div>    
        
    
 </form>
 </div>
 </div>
 
