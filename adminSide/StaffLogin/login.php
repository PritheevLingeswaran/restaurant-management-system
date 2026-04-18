<?php
session_start();
if (isset($_SESSION['logged_account_id'])) {
    header("Location: ../panel/pos-panel.php");
    exit;
}

$account_id = $account_id ?? '';
$password_err = $password_err ?? '';
$login_err = $login_err ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --login-bg: #14131b;
            --login-panel: rgba(18, 22, 31, 0.78);
            --login-card: rgba(255, 255, 255, 0.08);
            --login-accent: #b2763b;
            --login-accent-soft: #f2d7b1;
            --login-text: #f8f4ec;
            --login-muted: rgba(248, 244, 236, 0.72);
            --login-border: rgba(255, 255, 255, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Manrope', sans-serif;
            color: var(--login-text);
            background:
                linear-gradient(120deg, rgba(10, 12, 18, 0.92), rgba(10, 12, 18, 0.78)),
                radial-gradient(circle at top right, rgba(178, 118, 59, 0.18), transparent 32%),
                url('../../customerSide/image/loginBackground.jpg') center/cover no-repeat;
        }

        .staff-login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(320px, 1.1fr) minmax(360px, 0.9fr);
        }

        .staff-login-copy,
        .staff-login-panel {
            padding: 4.2rem clamp(2rem, 3.2vw, 4.2rem);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .staff-login-copy {
            background: linear-gradient(135deg, rgba(12, 16, 24, 0.78), rgba(12, 16, 24, 0.52));
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .staff-login-brand {
            margin-bottom: 3rem;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.4rem, 3.6vw, 3.8rem);
            letter-spacing: 0.28rem;
            text-transform: uppercase;
            color: var(--login-text);
            text-decoration: none;
        }

        .staff-login-tag {
            display: inline-block;
            margin-bottom: 1.4rem;
            font-size: 1.2rem;
            letter-spacing: 0.35rem;
            text-transform: uppercase;
            color: var(--login-accent-soft);
        }

        .staff-login-copy h1 {
            max-width: 42rem;
            margin-bottom: 1.4rem;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.5rem, 3.8vw, 3.9rem);
            line-height: 1.06;
        }

        .staff-login-copy p {
            max-width: 39rem;
            font-size: 1.2rem;
            line-height: 1.8;
            color: var(--login-muted);
        }

        .staff-login-points {
            display: grid;
            gap: 1.4rem;
            margin-top: 2rem;
        }

        .staff-login-points article {
            padding: 1.1rem 1.3rem;
            border-radius: 1.6rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
        }

        .staff-login-points strong {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.15rem;
            color: var(--login-accent-soft);
        }

        .staff-login-panel {
            background: rgba(11, 14, 20, 0.68);
        }

        .staff-login-card {
            width: min(100%, 36rem);
            margin: 0 auto;
            padding: 2rem;
            border-radius: 1.8rem;
            background: var(--login-panel);
            border: 1px solid var(--login-border);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
            backdrop-filter: blur(14px);
        }

        .staff-login-card h2 {
            margin-bottom: 0.6rem;
            font-size: 2.4rem;
            font-family: 'Cormorant Garamond', serif;
            color: var(--login-text);
        }

        .staff-login-card p {
            margin-bottom: 1.8rem;
            color: var(--login-muted);
            font-size: 1.15rem;
            line-height: 1.8;
        }

        .staff-login-alert {
            margin-bottom: 1.6rem;
            padding: 1.2rem 1.4rem;
            border-radius: 1.2rem;
            background: rgba(220, 53, 69, 0.16);
            color: #ffd5db;
            font-size: 1.35rem;
        }

        .staff-login-form {
            display: grid;
            gap: 1.1rem;
        }

        .staff-login-form label {
            display: inline-block;
            margin-bottom: 0.45rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--login-text);
        }

        .staff-login-form input {
            width: 100%;
            padding: 1rem 1.05rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.9);
            color: #161616;
            font-size: 1.2rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .staff-login-form input:focus {
            border-color: rgba(178, 118, 59, 0.65);
            box-shadow: 0 0 0 4px rgba(178, 118, 59, 0.16);
            transform: translateY(-1px);
        }

        .staff-login-submit {
            margin-top: 0.4rem;
            padding: 0.95rem 1.3rem;
            border: none;
            border-radius: 999px;
            background: linear-gradient(135deg, #8f5d2e, #b2763b);
            color: #fff9f1;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .staff-login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(0, 0, 0, 0.24);
        }

        .staff-login-back {
            display: inline-block;
            margin-top: 1.2rem;
            color: var(--login-accent-soft);
            text-decoration: none;
            font-size: 1rem;
        }

        .staff-login-back:hover {
            color: #fff;
        }

        @media (max-width: 991px) {
            .staff-login-shell {
                grid-template-columns: 1fr;
            }

            .staff-login-copy {
                padding-bottom: 2.4rem;
                border-right: none;
            }

            .staff-login-panel {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="staff-login-shell">
        <section class="staff-login-copy">
            <a class="staff-login-brand" href="../../customerSide/home/home.php">Boundless</a>
            <span class="staff-login-tag">Staff Access</span>
            <h1>Control the floor, service, and sales from one place.</h1>
            <p>
                Sign in to access reservations, point-of-sale, billing, kitchen coordination, and reporting tools used
                by the Boundless service team.
            </p>

            <div class="staff-login-points">
                <article>
                    <strong>Operations Dashboard</strong>
                    <span>Access billing, kitchen flow, staff actions, and business reporting.</span>
                </article>
                <article>
                    <strong>Live Restaurant Workflows</strong>
                    <span>Manage reservations, POS actions, and payments from a single staff panel.</span>
                </article>
            </div>
        </section>

        <section class="staff-login-panel">
            <div class="staff-login-card">
                <h2>Staff Login</h2>
                <p>Use your staff account ID and password to enter the operations dashboard.</p>

                <?php if (!empty($login_err)): ?>
                    <div class="staff-login-alert"><?php echo htmlspecialchars($login_err, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form action="login_process.php" method="post" class="staff-login-form">
                    <div>
                        <label for="account_id">Staff Account ID</label>
                        <input
                            type="number"
                            id="account_id"
                            name="account_id"
                            placeholder="Enter Account ID"
                            required
                            value="<?php echo htmlspecialchars((string) $account_id, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div>
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter Password"
                            required
                        >
                    </div>

                    <button class="staff-login-submit" type="submit" name="submit" value="Login">Enter Staff Panel</button>
                </form>

                <a class="staff-login-back" href="../../customerSide/home/home.php">Back to customer site</a>
            </div>
        </section>
    </div>
</body>
</html>
