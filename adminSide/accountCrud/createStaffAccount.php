<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php
require_once "../config.php";

$input_account_id = $account_iderr = $account_id = "";
$input_email = $email_err = $email = "";
$input_register_date = $register_date_err = $register_date = "";
$input_phone_number = $phone_number_err = $phone_number = "";
$input_password = $password_err = $password = "";
?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 860px;">
        <h2 class="pull-left">Create New Staff Account</h2>
        <p>Please fill in account information properly.</p>

        <form method="POST" action="success_create_staff_account.php">
            <div class="form-group">
                <label for="account_id" class="form-label">Account ID:</label>
                <input min="1" type="number" name="account_id" placeholder="99" class="form-control <?php echo !$account_idErr ?: 'is-invalid'; ?>" id="account_id" required value="<?php echo $account_id; ?>"><br>
                <div class="invalid-feedback">Please provide a valid account_id.</div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email :</label>
                <input type="text" name="email" placeholder="abc@gmail.com" class="form-control <?php echo !$emailErr ?: 'is-invalid'; ?>" id="email" required value="<?php echo $email; ?>"><br>
                <div class="invalid-feedback">Please provide a valid email.</div>
            </div>

            <div class="form-group">
                <label for="register_date">Register Date :</label>
                <input type="date" name="register_date" id="register_date" required class="form-control <?php echo !$register_date_err ?: 'is-invalid';?>" value="<?php echo $register_date; ?>"><br>
                <div class="invalid-feedback">Please provide a valid register date.</div>
            </div>

            <div class="form-group">
                <label for="phone_number" class="form-label">Phone Number:</label>
                <input type="text" name="phone_number" placeholder="" class="form-control <?php echo !$phone_numberErr ?: 'is-invalid'; ?>" id="phone_number" required value="<?php echo $phone_number; ?>"><br>
                <div class="invalid-feedback">Please provide a valid phone number.</div>
            </div>

            <div class="form-group">
                <label for="password">Password :</label>
                <input type="password" name="password" placeholder="abc1234@" id="password" required class="form-control <?php echo !$password_err ?: 'is-invalid' ; ?>" value="<?php echo $password; ?>"><br>
                <div class="invalid-feedback">Please provide a valid password.</div>
            </div>

            <div class="form-group">
                <input type="submit" name="submit" class="btn btn-dark" value="Create Account">
            </div>
        </form>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
