<?php
if (!isset($deleteVerifyTitle)) {
    $deleteVerifyTitle = 'Admin Login';
}
if (!isset($deleteVerifyMessage)) {
    $deleteVerifyMessage = 'Admin credentials are required.';
}
if (!isset($deleteVerifySubmitLabel)) {
    $deleteVerifySubmitLabel = 'Confirm';
}
if (!isset($deleteVerifyCancelHref)) {
    $deleteVerifyCancelHref = '../panel/pos-panel.php';
}
?>
<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 520px;">
        <h2 class="pull-left">Admin Login</h2>
        <p><?php echo htmlspecialchars($deleteVerifyMessage); ?></p>
        <form action="" method="post">
            <div class="form-group">
                <label>Admin Id</label>
                <input type="number" name="admin_id" class="form-control" placeholder="Enter Admin ID" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter Admin Password" required>
            </div>
            <button class="btn btn-dark" type="submit" name="submit" value="submit"><?php echo htmlspecialchars($deleteVerifySubmitLabel); ?></button>
            <a class="btn btn-light" href="<?php echo htmlspecialchars($deleteVerifyCancelHref); ?>">Cancel</a>
        </form>
    </div>
</div>
<?php include '../inc/dashFooter.php'; ?>
