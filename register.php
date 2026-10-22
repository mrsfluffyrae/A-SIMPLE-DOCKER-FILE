<?php
session_start();

$message = "";
$error = "";
$username_input = "";
$email_input = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_input = trim($_POST['username'] ?? '');
    $email_input    = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if ($username_input === '' || $email_input === '' || $password_input === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password_input) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
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

            // Securely hash password
            $hashed_password = password_hash($password_input, PASSWORD_BCRYPT);

            // Insert new user into database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username_input, $email_input, $hashed_password]);

            $message = "Account created successfully! You can now log in.";
            $username_input = "";
            $email_input = "";
        } catch (\PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                $error = "That username or email address is already registered.";
            } else {
                // Log details server-side; don't expose them to visitors
                error_log("Register DB error: " . $e->getMessage());
                $error = "Could not create the account. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributed System Demo - Register Account</title>
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

        input[type="text"],
        input[type="email"],
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
            background-color: #28a745;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #218838;
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
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .links a {
            color: #0056b3;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Create Account</h2>

        <?php if ($message !== ""): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars($username_input, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($email_input, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <button type="submit">Register</button>
        </form>

        <div class="links">
            Already have an account? <a href="index.php">Log in</a>
        </div>
    </div>
</body>
</html>
