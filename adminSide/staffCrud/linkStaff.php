<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php require_once "../config.php"; ?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 760px;">
        <h2 class="pull-left">Assign Staff to an Account</h2>
        <p>Please choose an account and a staff member to link together.</p>

        <form action="update_staff.php" method="post">
            <div class="form-group">
                <label for="account_id" class="form-label">Account ID:</label>
                <select id="account_id" name="account_id" class="form-control" required>
                    <option value="">Select an account</option>
                    <?php
                    $accountQuery = "SELECT account_id FROM Accounts WHERE staff_id IS NULL";
                    $accountResult = $link->query($accountQuery);
                    while ($row = $accountResult->fetch_assoc()) {
                        echo "<option value='" . $row['account_id'] . "'>" . $row['account_id'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="staff_id" class="form-label">Staff ID:</label>
                <select id="staff_id" name="staff_id" class="form-control" required>
                    <option value="">Select a staff</option>
                    <?php
                    $staffQuery = "SELECT staff_id FROM Staffs WHERE staff_id NOT IN (SELECT staff_id FROM Accounts WHERE staff_id IS NOT NULL)";
                    $staffResult = $link->query($staffQuery);
                    while ($row = $staffResult->fetch_assoc()) {
                        echo "<option value='" . $row['staff_id'] . "'>" . $row['staff_id'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-dark" value="Assign Account to Staff">
            </div>
        </form>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
