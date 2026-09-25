<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$conn = getDbConnection();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid email or password.';
    } else {
        $error = 'Please enter both email and password.';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EKBTECH Billing | Login</title>
    <style>
        :root {
            --bg: #081120;
            --panel: rgba(15, 23, 42, 0.8);
            --panel-strong: #0f172a;
            --primary: #e2e8f0;
            --muted: #94a3b8;
            --accent: #14b8a6;
            --accent-2: #3b82f6;
            --line: rgba(148, 163, 184, 0.2);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(59,130,246,0.26), transparent 35%),
                radial-gradient(circle at bottom right, rgba(20,184,166,0.24), transparent 28%),
                var(--bg);
            color: var(--primary);
        }

        .login-shell {
            width: min(420px, calc(100% - 30px));
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 28px 80px rgba(2, 6, 23, 0.6);
            padding: 30px 28px;
            backdrop-filter: blur(12px);
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 26px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            box-shadow: 0 14px 28px rgba(20,184,166,0.35);
        }

        h1 {
            margin: 0;
            text-align: center;
            font-size: 1.8rem;
            letter-spacing: -0.04em;
        }

        .subtext {
            text-align: center;
            color: var(--muted);
            margin: 10px 0 22px;
        }

        .alert {
            background: rgba(239,68,68,0.12);
            color: #fecaca;
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #dfe8f7;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: rgba(15, 23, 42, 0.8);
            color: white;
            font-size: 1rem;
        }

        input:focus {
            outline: none;
            border-color: rgba(20,184,166,0.7);
            box-shadow: 0 0 0 4px rgba(20,184,166,0.12);
        }

        .login-btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 13px 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
        }

        .meta {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="brand">
            <div class="brand-mark">N</div>
            <h1>EKBTECH</h1>
        </div>
        <div class="subtext">Invoice management dashboard</div>

        <?php if ($error !== ''): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="admin@northstar.com" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="admin123" required>
            </div>

            <button class="login-btn" type="submit">Sign in</button>
        </form>

        <div class="meta">Demo credentials: admin@northstar.com / admin123</div>
    </div>
</body>
</html>
