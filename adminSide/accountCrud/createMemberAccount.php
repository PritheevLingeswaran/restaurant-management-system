<?php include '../inc/dashHeader.php'; ?>
<?php include '../inc/legacyPanelLayout.php'; ?>
<?php
require_once "../config.php";

$email_err = $email = "";
$register_date_err = $register_date = date('Y-m-d');
$phone_number_err = $phone_number = "";
$password_err = $password = "";
$member_name_err = $member_name = "";

if (isset($_POST['submit'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: "";
    if ($email === "") {
        $email_err = 'Valid email is required.';
    }

    $register_date = trim($_POST['register_date'] ?? '');
    if ($register_date === "" || !DateTime::createFromFormat('Y-m-d', $register_date)) {
        $register_date_err = 'Valid register date is required.';
    }

    $phone_number = trim($_POST['phone_number'] ?? '');
    if ($phone_number === '') {
        $phone_number_err = 'Phone number is required.';
    }

    $password = trim($_POST['password'] ?? '');
    if ($password === '') {
        $password_err = 'Password is required.';
    }

    $member_name = trim($_POST['member_name'] ?? '');
    if ($member_name === '') {
        $member_name_err = 'Member name is required.';
    }
}
?>

<div class="legacy-wrapper">
    <div class="legacy-surface" style="max-width: 860px;">
        <h2 class="pull-left">Create New Member Account</h2>
        <p>Create a customer account and membership profile in one step.</p>

        <form method="POST" action="success_create_member_account.php">
            <div class="form-group">
                <label for="email" class="form-label">Email :</label>
                <input type="text" name="email" class="form-control <?php echo $email_err ? 'is-invalid' : ''; ?>" id="email" required placeholder="abc@gmail.com" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"><br>
                <div class="invalid-feedback"><?php echo $email_err ?: 'Please provide a valid email.'; ?></div>
            </div>

            <div class="form-group">
                <label for="register_date">Register Date :</label>
                <input type="date" name="register_date" id="register_date" required class="form-control <?php echo $register_date_err ? 'is-invalid' : ''; ?>" value="<?php echo $register_date; ?>"><br>
                <div class="invalid-feedback"><?php echo $register_date_err; ?></div>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number :</label>
                <input placeholder="" type="text" name="phone_number" id="phone_number" required class="form-control <?php echo $phone_number_err ? 'is-invalid' : ''; ?>" value="<?php echo $phone_number; ?>"><br>
                <div class="invalid-feedback"><?php echo $phone_number_err; ?></div>
            </div>

            <div class="form-group">
                <label for="member_name">Member Name :</label>
                <input placeholder="abc" type="text" name="member_name" id="member_name" required class="form-control <?php echo $member_name_err ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8'); ?>"><br>
                <div class="invalid-feedback"><?php echo $member_name_err; ?></div>
            </div>

            <div class="form-group">
                <label for="password">Password :</label>
                <input placeholder="abc1234@" type="password" name="password" id="password" required class="form-control <?php echo $password_err ? 'is-invalid' : ''; ?>"><br>
                <div class="invalid-feedback"><?php echo $password_err; ?></div>
            </div>

            <div class="form-group">
                <input type="submit" name="submit" class="btn btn-dark" value="Create Account">
            </div>
        </form>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>
