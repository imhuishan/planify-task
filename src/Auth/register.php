<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $email && $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header("Location: ../../public/index.php");
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed. Username or email might already exist.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Tasker.</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="justify-content: center; align-items: center; background: radial-gradient(circle at top left, #1e1b4b, #0f172a);">
    <div class="container" style="max-width: 450px;">
        <div class="glass-card" style="padding: 2.5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="margin-bottom: 0.5rem; font-size: 2.5rem;">Join Tasker<span class="gradient-text">.</span></h1>
                <p style="color: var(--text-secondary);">Start managing your team's tasks today.</p>
            </div>

            <?php if (isset($error)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 0.75rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name or Username</label>
                    <input type="text" name="username" class="form-control" placeholder="John Doe" required autofocus>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@company.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; height: 3rem;">
                    Create Account
                </button>
            </form>

            <p style="margin-top: 2rem; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
                Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none; border-bottom: 1px solid transparent; transition: var(--transition-smooth);" onmouseover="this.style.borderBottomColor='var(--primary-color)'" onmouseout="this.style.borderBottomColor='transparent'">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>
