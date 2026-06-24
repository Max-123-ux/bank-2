<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once 'config/invest_db.php';

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'fund_manager') {
        header('Location: fund-manager/manager-desk.php');
    } else {
        header('Location: investor/portfolio-view.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continental Investment Platform - Secure Login</title>
    <link rel="stylesheet" href="assets/css/wallstreet.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #212121 0%, #1a1a1a 100%);
        }
        .login-box {
            background: #2a2a2a;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid #8b0000;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #f7e7ce;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .login-header p {
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
        .form-group input {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #3d3d3d;
            color: #f7e7ce;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #8b0000;
        }
        .login-btn {
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
        .login-btn:hover {
            background: #a00000;
        }
        .register-btn {
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
        .register-btn:hover {
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
        .success-message {
            background: #006400;
            color: #f7e7ce;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #666;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #3d3d3d;
        }
        .divider span {
            padding: 0 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="ticker-tape" id="tickerTape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>CONTINENTAL INVEST</h1>
                <p>Institutional Investment Platform</p>
                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    Market Data: S&P 500 4,783.45 <span style="color: #00ff00;">↑2.3%</span> | NASDAQ 15,123.78 <span style="color: #00ff00;">↑1.8%</span>
                </div>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered'])): ?>
                <div class="success-message">Registration successful! Please login with your credentials.</div>
            <?php endif; ?>
            
            <form action="gateway/trader-login.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">ACCESS TERMINAL</button>
            </form>
            
            <div class="divider">
                <span>OR</span>
            </div>
            
            <a href="gateway/register.php" class="register-btn">CREATE NEW ACCOUNT</a>
        </div>
    </div>
    
    <script src="assets/js/ticker.js"></script>
</body>
</html>