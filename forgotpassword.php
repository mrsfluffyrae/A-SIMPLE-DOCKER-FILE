<?php
$message = "";
$error = "";
$email_input = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email_input = trim($_POST['email'] ?? '');

    if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } else {
        // 1. Establish database connection matching docker-compose
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

            // Check if the email exists in the users table
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email_input]);
            $user_record = $stmt->fetch();

            if ($user_record) {
                // Generate a random reset token
                $token = bin2hex(random_bytes(16));

                // Save token to database
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
                $update_stmt->execute([$token, $email_input]);

                // 2. Construct email payload
                $to         = $email_input;
                $subject    = "Password Reset Request - Distributed Demo App";
                $reset_link = "http://localhost:8080/reset-password.php?token=" . urlencode($token);

                $email_body = "Hello,\n\n"
                    . "You requested a password reset. Click the link below to change it:\n\n"
                    . $reset_link . "\n\n"
                    . "If you did not request this, please ignore this message.";

                $headers = "From: system-noreply@kabarak.ac.ke\r\n"
                         . "Reply-To: support@kabarak.ac.ke\r\n"
                         . "X-Mailer: PHP/" . phpversion();

                // On Linux, mail() sends through sendmail_path (msmtp), which relays to the
                // Docker 'mail' container. See php-mail.ini and msmtprc.
                if (mail($to, $subject, $email_body, $headers)) {
                    $message = "A reset password message has been sent to your email!";
                } else {
                    error_log("mail() failed for password reset request");
                    $error = "Failed to dispatch email over the mail server network.";
                }
            } else {
                $error = "No user account found matching that email address.";
            }
        } catch (\PDOException $e) {
            // Log details server-side; don't expose them to visitors
            error_log("Forgot password DB error: " . $e->getMessage());
            $error = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributed System Demo - Forgot Password</title>
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
            width: 340px;
        }

        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }

        p {
            color: #666;
            font-size: 14px;
            text-align: center;
            line-height: 1.4;
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

        input[type="email"] {
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
        <h2>Task A: Reset Password</h2>
        <p>Enter your registration email address below to send an asynchronous message update request.</p>

        <?php if ($message !== ""): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="forgotpassword.php" method="POST">
            <div class="form-group">
                <label for="email">Your Account Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="e.g., admin@kabarak.ac.ke"
                    value="<?= htmlspecialchars($email_input, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>
            <button type="submit">Send Reset Message</button>
        </form>

        <div class="links">
            <a href="index.php">&larr; Back to Login Screen</a>
        </div>
    </div>
</body>
</html>
