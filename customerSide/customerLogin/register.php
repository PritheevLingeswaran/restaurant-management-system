<?php
require_once '../config.php';
session_start();

$email = $member_name = $password = $phone_number = "";
$email_err = $member_name_err = $password_err = $phone_number_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email. Ex: johndoe@email.com";
    } else {
        $email = trim($_POST["email"]);
    }

    $selectCreatedEmail = "SELECT email FROM Accounts WHERE email = ?";
    if ($stmt = $link->prepare($selectCreatedEmail)) {
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $email_err = "This email is already registered.";
        } else {
            $email = trim($_POST["email"]);
        }
        $stmt->close();
    }

    if (empty(trim($_POST["member_name"]))) {
        $member_name_err = "Please enter your member name.";
    } else {
        $member_name = trim($_POST["member_name"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty(trim($_POST["phone_number"]))) {
        $phone_number_err = "Please enter your phone number.";
    } elseif (!is_numeric(trim($_POST['phone_number']))) {
        $phone_number_err = "Only enter numeric values.";
    } else {
        $phone_number = trim($_POST["phone_number"]);
    }

    if (empty($email_err) && empty($member_name_err) && empty($password_err) && empty($phone_number_err)) {
        mysqli_begin_transaction($link);

        $sql_accounts = "INSERT INTO Accounts (email, password, phone_number, register_date) VALUES (?, ?, ?, NOW())";
        if ($stmt_accounts = mysqli_prepare($link, $sql_accounts)) {
            mysqli_stmt_bind_param($stmt_accounts, "sss", $param_email, $param_password, $param_phone_number);

            $param_email = $email;
            $param_password = $password;
            $param_phone_number = $phone_number;
        }

        if (mysqli_stmt_execute($stmt_accounts)) {
            $last_account_id = mysqli_insert_id($link);

            $sql_memberships = "INSERT INTO Memberships (member_name, points, account_id) VALUES (?, ?, ?)";
            if ($stmt_memberships = mysqli_prepare($link, $sql_memberships)) {
                mysqli_stmt_bind_param($stmt_memberships, "sii", $param_member_name, $param_points, $last_account_id);

                $param_member_name = $member_name;
                $param_points = 0;

                if (mysqli_stmt_execute($stmt_memberships)) {
                    mysqli_commit($link);
                    header("location: register_process.php");
                    exit;
                } else {
                    mysqli_rollback($link);
                    $password_err = "Something went wrong. Please try again later.";
                }

                mysqli_stmt_close($stmt_memberships);
            }
        } else {
            mysqli_rollback($link);
            $password_err = "Something went wrong. Please try again later.";
        }

        mysqli_stmt_close($stmt_accounts);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --auth-bg: #14131b;
            --auth-card: rgba(18, 22, 31, 0.8);
            --auth-panel: rgba(255, 255, 255, 0.06);
            --auth-accent: #b2763b;
            --auth-accent-soft: #f2d7b1;
            --auth-text: #f8f4ec;
            --auth-muted: rgba(248, 244, 236, 0.74);
            --auth-border: rgba(255, 255, 255, 0.12);
            --auth-danger: #ffccd4;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Manrope', sans-serif;
            color: var(--auth-text);
            background:
                linear-gradient(120deg, rgba(12, 16, 24, 0.9), rgba(12, 16, 24, 0.72)),
                radial-gradient(circle at top right, rgba(178, 118, 59, 0.18), transparent 32%),
                url('../image/loginBackground.jpg') center/cover no-repeat;
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(320px, 1.08fr) minmax(360px, 0.92fr);
        }

        .auth-copy,
        .auth-panel-wrap {
            padding: 3.8rem clamp(1.8rem, 3vw, 4rem);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-copy {
            background: linear-gradient(135deg, rgba(12, 16, 24, 0.78), rgba(12, 16, 24, 0.5));
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .auth-brand {
            margin-bottom: 2.4rem;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.3rem, 3.5vw, 3.5rem);
            letter-spacing: 0.24rem;
            text-transform: uppercase;
            color: var(--auth-text);
            text-decoration: none;
        }

        .auth-tag {
            display: inline-block;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            letter-spacing: 0.32rem;
            text-transform: uppercase;
            color: var(--auth-accent-soft);
        }

        .auth-copy h1 {
            max-width: 38rem;
            margin-bottom: 1.1rem;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.2rem, 3.4vw, 3.5rem);
            line-height: 1.08;
        }

        .auth-copy p {
            max-width: 37rem;
            font-size: 1.05rem;
            line-height: 1.75;
            color: var(--auth-muted);
        }

        .auth-points {
            display: grid;
            gap: 1.2rem;
            margin-top: 1.6rem;
        }

        .auth-points article {
            padding: 1rem 1.2rem;
            border-radius: 1.5rem;
            background: var(--auth-panel);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
        }

        .auth-points strong {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 1.05rem;
            color: var(--auth-accent-soft);
        }

        .auth-panel-wrap {
            background: rgba(11, 14, 20, 0.66);
        }

        .auth-card {
            width: min(100%, 33rem);
            margin: 0 auto;
            padding: 1.6rem;
            border-radius: 1.9rem;
            background: var(--auth-card);
            border: 1px solid var(--auth-border);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
            backdrop-filter: blur(14px);
        }

        .auth-card h2 {
            margin-bottom: 0.6rem;
            font-size: 1.95rem;
            font-family: 'Cormorant Garamond', serif;
        }

        .auth-card > p {
            margin-bottom: 1.2rem;
            font-size: 0.94rem;
            line-height: 1.8;
            color: var(--auth-muted);
        }

        .auth-form {
            display: grid;
            gap: 0.82rem;
        }

        .auth-form label {
            display: inline-block;
            margin-bottom: 0.35rem;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .auth-form input {
            width: 100%;
            padding: 0.82rem 0.92rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.92);
            color: #161616;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-form input:focus {
            border-color: rgba(178, 118, 59, 0.65);
            box-shadow: 0 0 0 4px rgba(178, 118, 59, 0.16);
        }

        .auth-error {
            margin-top: 0.35rem;
            font-size: 0.95rem;
            color: var(--auth-danger);
        }

        .auth-submit,
        .auth-google {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.8rem 0.95rem;
            border-radius: 999px;
            font-size: 0.94rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-submit {
            margin-top: 0.3rem;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, #8f5d2e, #b2763b);
            color: #fff9f1;
        }

        .auth-google {
            margin-top: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.94);
            color: #1d1d1d;
            gap: 0.7rem;
        }

        .auth-submit:hover,
        .auth-google:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(0, 0, 0, 0.24);
        }

        .auth-divider {
            position: relative;
            margin: 1.15rem 0 0.2rem;
            text-align: center;
            color: var(--auth-muted);
            font-size: 0.88rem;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 36%;
            height: 1px;
            background: rgba(255, 255, 255, 0.12);
        }

        .auth-divider::before { left: 0; }
        .auth-divider::after { right: 0; }

        .auth-switch {
            margin-top: 1rem;
            font-size: 0.86rem;
            color: var(--auth-muted);
        }

        .auth-switch a,
        .auth-back {
            color: var(--auth-accent-soft);
            text-decoration: none;
        }

        .auth-back {
            display: inline-block;
            margin-top: 0.75rem;
            font-size: 0.84rem;
        }

        .auth-switch a:hover,
        .auth-back:hover {
            color: #fff;
        }

        @media (max-width: 991px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-copy {
                border-right: none;
                padding-bottom: 2rem;
            }

            .auth-panel-wrap {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <section class="auth-copy">
            <a class="auth-brand" href="../home/home.php">Boundless</a>
            <span class="auth-tag">Create Account</span>
            <h1>Join Boundless and make every reservation feel easier.</h1>
            <p>
                Create your customer account to access booking convenience, membership tracking, and a more polished
                dining experience every time you return.
            </p>

            <div class="auth-points">
                <article>
                    <strong>Faster Reservations</strong>
                    <span>Save your details and move through the booking flow with less friction.</span>
                </article>
                <article>
                    <strong>Member Rewards</strong>
                    <span>Build points and maintain a connected account for repeat visits.</span>
                </article>
            </div>
        </section>

        <section class="auth-panel-wrap">
            <div class="auth-card">
                <h2>Create Account</h2>
                <p>Fill in your details to register as a Boundless customer.</p>

                <form action="register.php" method="post" class="auth-form">
                    <div>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($email_err !== ''): ?><div class="auth-error"><?php echo htmlspecialchars($email_err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>

                    <div>
                        <label for="member_name">Member Name</label>
                        <input type="text" id="member_name" name="member_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($member_name_err !== ''): ?><div class="auth-error"><?php echo htmlspecialchars($member_name_err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>

                    <div>
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password">
                        <?php if ($password_err !== ''): ?><div class="auth-error"><?php echo htmlspecialchars($password_err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>

                    <div>
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($phone_number, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($phone_number_err !== ''): ?><div class="auth-error"><?php echo htmlspecialchars($phone_number_err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>

                    <button class="auth-submit" type="submit" name="register" value="Register">Create Account</button>
                </form>

                <p class="auth-switch">Already have an account? <a href="login.php">Sign in here</a></p>
                <a class="auth-back" href="../home/home.php">Back to home</a>
            </div>
        </section>
    </div>
</body>
</html>
