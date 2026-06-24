<?php
require_once '../config/premier_db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $initialDeposit = floatval($_POST['initial_deposit']);
    
    // Validation
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($username) || strlen($username) < 4) {
        $errors[] = 'Username must be at least 4 characters';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if ($initialDeposit < 100) {
        $errors[] = 'Minimum initial deposit is $100.00';
    }
    
    if (empty($errors)) {
        $db = PremierDB::getInstance()->getConnection();
        
        try {
            // Check if username exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM account_holders WHERE ah_username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists');
            }
            
            // Check if email exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM account_holders WHERE ah_email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email already registered');
            }
            
            $db->beginTransaction();
            
            // Generate unique IBAN
            $iban = PremierDB::generateIBAN();
            
            // Ensure IBAN is unique
            $stmt = $db->prepare("SELECT COUNT(*) FROM account_holders WHERE ah_iban = ?");
            $stmt->execute([$iban]);
            while ($stmt->fetchColumn() > 0) {
                $iban = PremierDB::generateIBAN();
                $stmt->execute([$iban]);
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Create account
            $stmt = $db->prepare("INSERT INTO account_holders (ah_username, ah_password, ah_fullname, ah_email, ah_phone, ah_iban, ah_balance, ah_role) VALUES (?, ?, ?, ?, ?, ?, ?, 'client')");
            $stmt->execute([$username, $hashedPassword, $fullname, $email, $phone, $iban, $initialDeposit]);
            
            $accountId = $db->lastInsertId();
            
            // Record initial deposit transaction
            $reference = PremierDB::generateReference();
            $stmt = $db->prepare("INSERT INTO transaction_records (tr_reference, tr_account_id, tr_type, tr_amount, tr_balance_before, tr_balance_after, tr_description) VALUES (?, ?, 'credit', ?, 0.00, ?, 'Initial Portfolio Deposit')");
            $stmt->execute([$reference, $accountId, $initialDeposit, $initialDeposit]);
            
            $db->commit();
            
            $success = "Account created successfully! Your IBAN is: {$iban}. Please login to access your portfolio.";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Account - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
    <style>
        body.enroll-page {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .enroll-container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(183, 110, 121, 0.2);
            padding: 60px;
            max-width: 600px;
            width: 100%;
            border-radius: 2px;
        }
        
        .enroll-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .enroll-header h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 2rem;
            color: var(--rose-gold);
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        
        .enroll-header p {
            color: rgba(245, 245, 240, 0.7);
            letter-spacing: 2px;
            font-size: 0.85rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: var(--rose-gold-light);
            font-size: 0.8rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-family: 'Cormorant Garamond', Georgia, serif;
        }
        
        .form-input-enroll {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(183, 110, 121, 0.3);
            color: var(--marble-white);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.1rem;
            letter-spacing: 1px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .form-input-enroll:focus {
            border-color: var(--rose-gold);
            box-shadow: 0 0 15px rgba(183, 110, 121, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-input-enroll::placeholder {
            color: rgba(245, 245, 240, 0.3);
        }
        
        .btn-enroll {
            width: 100%;
            padding: 15px;
            background: var(--rose-gold);
            border: none;
            color: white;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-enroll:hover {
            background: var(--gold-accent);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(183, 110, 121, 0.3);
        }
        
        .back-link {
            display: block;
            text-align: center;
            color: rgba(245, 245, 240, 0.5);
            text-decoration: none;
            margin-top: 25px;
            letter-spacing: 1px;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--rose-gold-light);
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .enroll-container {
                padding: 30px;
            }
        }
    </style>
</head>
<body class="enroll-page">
    <div class="enroll-container">
        <div class="enroll-header">
            <h1>OPEN ACCOUNT</h1>
            <p>Begin Your Premier Banking Journey</p>
        </div>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success" style="background: rgba(45, 90, 39, 0.2); border-left-color: var(--success-green); color: #6abf69;">
                <?php echo $success; ?>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="../index.php" class="btn-enroll" style="display: inline-block; text-decoration: none; width: auto; padding: 15px 40px;">
                    Proceed to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="fullname">Full Legal Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-input-enroll" required placeholder="Enter your full name as per ID">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-input-enroll" required placeholder="Choose a username">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input-enroll" required placeholder="your@email.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input-enroll" required placeholder="+1234567890">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input-enroll" required placeholder="Minimum 6 characters">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input-enroll" required placeholder="Re-enter password">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="initial_deposit">Initial Portfolio Deposit ($)</label>
                    <input type="number" id="initial_deposit" name="initial_deposit" class="form-input-enroll" required placeholder="Minimum $100.00" step="0.01" min="100">
                </div>
                
                <div style="margin: 30px 0; padding: 20px; background: rgba(183, 110, 121, 0.05); border-left: 2px solid var(--rose-gold);">
                    <p style="color: rgba(245, 245, 240, 0.6); font-size: 0.85rem; letter-spacing: 1px; margin: 0;">
                        By opening an account, you agree to Premier Commercial Bank's terms and conditions. Your information is encrypted and protected by bank-grade security.
                    </p>
                </div>
                
                <button type="submit" class="btn-enroll">Open Account</button>
            </form>
            
            <a href="../index.php" class="back-link">← Return to Login</a>
        <?php endif; ?>
    </div>
    
    <script>
        // Client-side validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>