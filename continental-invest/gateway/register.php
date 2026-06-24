<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 4) {
        $error = "Username must be at least 4 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ['investor', 'fund_manager'])) {
        $error = "Invalid role selected.";
    } else {
        // Check if username already exists
        $checkSql = "SELECT pt_id FROM portfolios WHERE pt_username = '$username'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            $error = "Username already exists. Please choose another.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate portfolio code
            $prefix = ($role == 'fund_manager') ? 'FM-' : 'INV-';
            $result = $conn->query("SELECT MAX(pt_id) as max_id FROM portfolios");
            $row = $result->fetch_assoc();
            $nextId = ($row['max_id'] ?? 0) + 1;
            $portfolioCode = $prefix . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            
            // Insert new user
            $insertSql = "INSERT INTO portfolios (pt_username, pt_password, pt_role, pt_aum, pt_portfolio_code) 
                         VALUES ('$username', '$hashedPassword', '$role', 0.00, '$portfolioCode')";
            
            if ($conn->query($insertSql)) {
                $success = true;
                // Redirect to login page
                header('Location: ../index.php?registered=1');
                exit();
            } else {
                $error = "Registration failed: " . $conn->error;
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
    <title>Register - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #212121 0%, #1a1a1a 100%);
        }
        .register-box {
            background: #2a2a2a;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 450px;
            border: 1px solid #8b0000;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #f7e7ce;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .register-header p {
            color: #8b0000;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #f7e7ce;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #3d3d3d;
            color: #f7e7ce;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group select option {
            background: #2a2a2a;
            color: #f7e7ce;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8b0000;
        }
        .register-submit-btn {
            width: 100%;
            padding: 12px;
            background: #8b0000;
            color: #f7e7ce;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .register-submit-btn:hover {
            background: #a00000;
        }
        .back-to-login {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: #f7e7ce;
            border: 1px solid #f7e7ce;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .back-to-login:hover {
            background: #f7e7ce;
            color: #212121;
        }
        .error-message {
            background: #8b0000;
            color: #f7e7ce;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .password-requirements {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
        .role-description {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="ticker-tape" id="tickerTape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <h1>CREATE ACCOUNT</h1>
                <p>Continental Investment Platform</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    <div class="password-requirements">Minimum 4 characters</div>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <div class="password-requirements">Minimum 6 characters</div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="role" required>
                        <option value="investor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'investor') ? 'selected' : ''; ?>>Investor</option>
                        <option value="fund_manager" <?php echo (isset($_POST['role']) && $_POST['role'] == 'fund_manager') ? 'selected' : ''; ?>>Fund Manager</option>
                    </select>
                    <div class="role-description">
                        Investor: Portfolio management, trading, and asset allocation<br>
                        Fund Manager: Full platform administration and oversight
                    </div>
                </div>
                
                <button type="submit" class="register-submit-btn">CREATE ACCOUNT</button>
            </form>
            
            <a href="../index.php" class="back-to-login">BACK TO LOGIN</a>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>