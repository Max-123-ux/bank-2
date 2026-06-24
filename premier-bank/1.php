<?php
/**
 * Premier Bank - Admin Password Reset Tool
 * 
 * IMPORTANT: Delete this file after use for security reasons!
 * 
 * Usage: 
 * 1. Place this file in your premier-bank folder
 * 2. Access it via: http://localhost/premier-bank/reset-admin.php
 * 3. Click "Reset Admin Account"
 * 4. Delete this file immediately after
 */

require_once 'config/premier_db.php';

$message = '';
$messageType = '';

// Process reset request
if (isset($_POST['reset']) && $_POST['confirm'] === 'RESET') {
    try {
        $db = PremierDB::getInstance()->getConnection();
        
        // Default credentials
        $username = 'admin';
        $password = 'admin123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if admin exists
        $stmt = $db->prepare("SELECT ah_id FROM account_holders WHERE ah_username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            // Update existing admin
            $stmt = $db->prepare("UPDATE account_holders SET ah_password = ?, ah_failed_attempts = 0, ah_locked_until = NULL, ah_status = 'active' WHERE ah_username = ?");
            $stmt->execute([$hashedPassword, $username]);
            $message = "Admin account UPDATED successfully!";
        } else {
            // Create new admin
            $iban = 'PB-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("INSERT INTO account_holders (ah_username, ah_password, ah_fullname, ah_email, ah_iban, ah_role, ah_status) VALUES (?, ?, ?, ?, ?, 'bank_officer', 'active')");
            $stmt->execute([$username, $hashedPassword, 'Premier Bank Officer', 'officer@premier-bank.com', $iban]);
            $message = "Admin account CREATED successfully!";
        }
        
        $messageType = 'success';
        $message .= "<br><br>Login credentials:<br>";
        $message .= "<strong>Username:</strong> admin<br>";
        $message .= "<strong>Password:</strong> admin123<br>";
        $message .= "<strong>Role:</strong> Bank Officer<br><br>";
        $message .= "<a href='index.php' style='color: #b76e79;'>Go to Login Page</a>";
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset - Premier Bank</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #1a1a1a;
            font-family: 'Georgia', serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .reset-container {
            background: #2d2d2d;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            border: 1px solid #b76e79;
            box-shadow: 0 10px 40px rgba(183, 110, 121, 0.2);
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .reset-header h1 {
            color: #b76e79;
            font-size: 2rem;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        
        .reset-header p {
            color: #888;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }
        
        .warning-box {
            background: rgba(139, 0, 0, 0.2);
            border-left: 3px solid #8b0000;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning-box p {
            color: #ff6b6b;
            font-size: 0.9rem;
            line-height: 1.6;
            letter-spacing: 0.5px;
        }
        
        .warning-box strong {
            color: #ff4444;
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: #d4a5a5;
            margin-bottom: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: #1a1a1a;
            border: 1px solid #b76e79;
            color: #f5f5f0;
            font-family: 'Georgia', serif;
            font-size: 1rem;
            letter-spacing: 1px;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: #c9a96e;
            box-shadow: 0 0 15px rgba(183, 110, 121, 0.3);
        }
        
        .btn-reset {
            width: 100%;
            padding: 15px;
            background: #b76e79;
            border: none;
            color: white;
            font-size: 1rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Georgia', serif;
        }
        
        .btn-reset:hover {
            background: #c9a96e;
            transform: translateY(-2px);
        }
        
        .btn-reset:disabled {
            background: #555;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 2px;
        }
        
        .message.success {
            background: rgba(45, 90, 39, 0.3);
            border-left: 3px solid #2d5a27;
            color: #6abf69;
            line-height: 1.8;
        }
        
        .message.error {
            background: rgba(139, 0, 0, 0.3);
            border-left: 3px solid #8b0000;
            color: #ff6b6b;
        }
        
        .message a {
            color: #b76e79;
            text-decoration: none;
            font-weight: bold;
        }
        
        .message a:hover {
            text-decoration: underline;
        }
        
        .security-notice {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #444;
            text-align: center;
        }
        
        .security-notice p {
            color: #666;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>ADMIN RESET</h1>
            <p>Premier Commercial Bank</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$message || $messageType === 'error'): ?>
            <div class="warning-box">
                <strong>⚠️ SECURITY WARNING</strong>
                <p>
                    This tool resets the admin account to default credentials.<br>
                    <strong>Delete this file (reset-admin.php) immediately after use!</strong>
                </p>
            </div>
            
            <form method="POST" onsubmit="return confirmReset()">
                <div class="form-group">
                    <label>Confirmation</label>
                    <input type="text" name="confirm" placeholder="Type RESET to confirm" required autocomplete="off">
                </div>
                
                <button type="submit" name="reset" class="btn-reset">
                    Reset Admin Account
                </button>
            </form>
            
            <div class="security-notice">
                <p>
                    Default: admin / admin123<br>
                    Officer ID: PO-001
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function confirmReset() {
            const input = document.querySelector('input[name="confirm"]');
            if (input.value !== 'RESET') {
                alert('Please type RESET in the confirmation field');
                return false;
            }
            return confirm('Are you sure you want to reset the admin account?');
        }
    </script>
</body>
</html>