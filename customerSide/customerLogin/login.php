<?php
require_once '../config.php';
session_start();

$email = $password = "";
$email_err = $password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT * FROM Accounts WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_assoc($result);

                    if ($password === $row["password"]) {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["email"] = $email;

                        $sql_member = "SELECT * FROM Memberships WHERE account_id = " . (int) $row['account_id'];
                        $result_member = mysqli_query($link, $sql_member);

                        if ($result_member) {
                            $membership_row = mysqli_fetch_assoc($result_member);

                            if ($membership_row) {
                                $_SESSION["account_id"] = $membership_row["account_id"];
                                $_SESSION["member_name"] = $membership_row["member_name"];
                                header("location: ../home/home.php");
                                exit;
                            } else {
                                $password_err = "No membership details found for this account.";
                            }
                        } else {
                            $password_err = "Error fetching membership details: " . mysqli_error($link);
                        }
                    } else {
                        $password_err = "Invalid password. Please try again.";
                    }
                } else {
                    $email_err = "No account found with this email.";
                }
            } else {
                $password_err = "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
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
            width: min(100%, 35rem);
            margin: 0 auto;
            padding: 1.9rem;
            border-radius: 1.9rem;
            background: var(--auth-card);
            border: 1px solid var(--auth-border);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
            backdrop-filter: blur(14px);
        }

        .auth-card h2 {
            margin-bottom: 0.6rem;
            font-size: 2.15rem;
            font-family: 'Cormorant Garamond', serif;
        }

        .auth-card > p {
            margin-bottom: 1.5rem;
            font-size: 1rem;
            line-height: 1.8;
            color: var(--auth-muted);
        }

        .auth-form {
            display: grid;
            gap: 0.95rem;
        }

        .auth-form label {
            display: inline-block;
            margin-bottom: 0.45rem;
            font-size: 0.98rem;
            font-weight: 600;
        }

        .auth-form input {
            width: 100%;
            padding: 0.92rem 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.92);
            color: #161616;
            font-size: 1.02rem;
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
            padding: 0.88rem 1.05rem;
            border-radius: 999px;
            font-size: 1rem;
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
            margin: 1.4rem 0 0.3rem;
            text-align: center;
            color: var(--auth-muted);
            font-size: 0.95rem;
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
            margin-top: 1.2rem;
            font-size: 0.92rem;
            color: var(--auth-muted);
        }

        .auth-switch a,
        .auth-back {
            color: var(--auth-accent-soft);
            text-decoration: none;
        }

        .auth-back {
            display: inline-block;
            margin-top: 0.85rem;
            font-size: 0.9rem;
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
            <span class="auth-tag">Guest Access</span>
            <h1>Sign in to manage reservations, rewards, and your dining experience.</h1>
            <p>
                Access your customer account to book tables faster, view membership details, and continue enjoying the
                premium Boundless experience across reservations and rewards.
            </p>

            <div class="auth-points">
                <article>
                    <strong>Reservations Made Simple</strong>
                    <span>Reuse your member profile for a smoother booking flow.</span>
                </article>
                <article>
                    <strong>Membership Benefits</strong>
                    <span>Track points and enjoy a more personalized dining journey.</span>
                </article>
            </div>
        </section>

        <section class="auth-panel-wrap">
            <div class="auth-card">
                <h2>Customer Login</h2>
                <p>Use your email and password to continue to your Boundless account.</p>

                <form action="login.php" method="post" class="auth-form">
                    <div>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($email_err !== ''): ?><div class="auth-error"><?php echo htmlspecialchars($email_err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>

                    <div>
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <?php if ($password_err !== ''): ?><div class="auth-error"><?php echo htmlspecialchars($password_err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    </div>

                    <button class="auth-submit" type="submit" name="submit" value="Login">Sign In</button>
                </form>

                <p class="auth-switch">Don’t have an account? <a href="register.php">Create one now</a></p>
                <a class="auth-back" href="../home/home.php">Back to home</a>
            </div>
        </section>
    </div>
</body>
</html>
