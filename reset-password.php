<?php
session_start();

$message = "";
$error = "";
$token_valid = false;
$user_record = null;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// 1. Establish database connection
$host    = 'db';
$db      = 'contact_db';
$db_user = 'root';
$db_pass = 'secret_pass';
$charset = 'utf8mb4';
$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    // Log details server-side; don't expose them to visitors
    error_log("Reset DB connection error: " . $e->getMessage());
    die("Service temporarily unavailable. Please try again later.");
}

// 2. Validate the token
if ($token === '') {
    $error = "Invalid or expired security validation token.";
} else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user_record = $stmt->fetch();

    if (!$user_record) {
        $error = "The token is invalid or has already been used to reset a password.";
    } else {
        $token_valid = true;
    }
}

// 3. Process the new password submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $token_valid) {
    $new_password = $_POST['password'] ?? '';

    if ($new_password === '') {
        $error = "Please enter a new password.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the password and wipe the reset token so it can't be reused
        $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
        $update_stmt->execute([$hashed_password, $user_record['id']]);

        $message = "Your password has been successfully updated! You can now log in.";
        $token_valid = false; // hide the form after success
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributed System Demo - Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 320px;
        }

        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-weight: bold;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #0056b3;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #004085;
        }

        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .links {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        .links a {
            color: #0056b3;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Update Password</h2>

        <?php if ($message !== ""): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($token_valid): ?>
            <form action="reset-password.php?token=<?= urlencode($token) ?>" method="POST">
                <div class="form-group">
                    <label for="password">Enter New Password</label>
                    <input type="password" id="password" name="password" required minlength="8"
                           placeholder="Type new password">
                </div>
                <button type="submit">Update Password Record</button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="index.php">← Go to Login Page</a>
        </div>
    </div>
</body>

</html>
