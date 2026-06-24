<?php
require_once 'config/premier_db.php';

$error = '';
$timeout_message = '';
$success_message = '';

if (isset($_GET['timeout'])) {
    $timeout_message = 'Your session has expired. Please login again.';
}

if (isset($_GET['enrolled'])) {
    $success_message = 'Account created successfully. Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'All fields are required';
    } else {
        $db = PremierDB::getInstance()->getConnection();
        
        // Check if account is locked
        $stmt = $db->prepare("SELECT * FROM account_holders WHERE ah_username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check lockout
            if ($user['ah_locked_until'] && strtotime($user['ah_locked_until']) > time()) {
                $lockTime = date('H:i:s', strtotime($user['ah_locked_until']));
                $error = "Account locked until {$lockTime}. Please try again later.";
            } else {
                // Verify password
                if (password_verify($password, $user['ah_password'])) {
                    // Reset failed attempts
                    $stmt = $db->prepare("UPDATE account_holders SET ah_failed_attempts = 0, ah_locked_until = NULL WHERE ah_id = ?");
                    $stmt->execute([$user['ah_id']]);
                    
                    // Set session
                    $_SESSION['user_id'] = $user['ah_id'];
                    $_SESSION['username'] = $user['ah_username'];
                    $_SESSION['fullname'] = $user['ah_fullname'];
                    $_SESSION['role'] = $user['ah_role'];
                    $_SESSION['iban'] = $user['ah_iban'];
                    $_SESSION['last_activity'] = time();
                    
                    // Redirect based on role
                    if ($user['ah_role'] === 'bank_officer') {
                        header('Location: officer/officer-panel.php');
                    } else {
                        header('Location: client/portfolio.php');
                    }
                    exit();
                } else {
                    // Increment failed attempts
                    $failedAttempts = $user['ah_failed_attempts'] + 1;
                    $lockUntil = null;
                    
                    if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
                        $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
                        $error = "Account locked for 15 minutes due to multiple failed attempts.";
                    } else {
                        $remaining = MAX_LOGIN_ATTEMPTS - $failedAttempts;
                        $error = "Invalid credentials. {$remaining} attempts remaining.";
                    }
                    
                    $stmt = $db->prepare("UPDATE account_holders SET ah_failed_attempts = ?, ah_locked_until = ? WHERE ah_id = ?");
                    $stmt->execute([$failedAttempts, $lockUntil, $user['ah_id']]);
                }
            }
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premier Commercial Bank - Private Banking</title>
    <link rel="stylesheet" href="assets/css/luxury.css">
    <style>
        .enroll-link {
            color: var(--rose-gold-light);
            text-decoration: none;
            font-family: 'Cormorant Garamond', Georgia, serif;
            letter-spacing: 2px;
            font-size: 0.9rem;
            margin-top: 30px;
            display: inline-block;
            transition: color 0.3s ease;
            opacity: 0.7;
        }
        
        .enroll-link:hover {
            color: var(--rose-gold);
            opacity: 1;
        }
        
        .divider {
            width: 50px;
            height: 1px;
            background: var(--rose-gold);
            margin: 30px auto;
            opacity: 0.3;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="bank-crest">PREMIER</div>
        <div class="bank-subtitle">Private Banking</div>
        
        <?php if ($timeout_message): ?>
            <div class="message error"><?php echo $timeout_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" name="username" class="input-field-gold" placeholder="USERNAME" required autocomplete="off">
            <input type="password" name="password" class="input-field-gold" placeholder="PASSWORD" required>
            <button type="submit" class="btn-gold">Enter</button>
        </form>
        
        <div class="divider"></div>
        
        <a href="entrance/enroll.php" class="enroll-link">Open a New Account</a>
    </div>
</body>
</html>