<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    $portfolioCode = $_SESSION['portfolio_code'];
    
    if ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } else {
        // Generate confirmation code
        $confirmationCode = 'TC-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Update AUM
        $updateSql = "UPDATE portfolios SET pt_aum = pt_aum + $amount WHERE pt_portfolio_code = '$portfolioCode'";
        
        // Insert trade ticket
        $tradeSql = "INSERT INTO trade_history (th_portfolio_code, th_trade_type, th_amount, th_direction, th_confirmation_code) 
                     VALUES ('$portfolioCode', 'Capital Injection', $amount, 'inflow', '$confirmationCode')";
        
        if ($conn->query($updateSql) && $conn->query($tradeSql)) {
            // Redirect to confirmation
            header("Location: trade-confirm.php?code=$confirmationCode");
            exit();
        } else {
            $error = "Transaction failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capital Injection - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>CAPITAL INJECTION</h1>
            <div class="user-info">
                <a href="portfolio-view.php" class="logout-btn">BACK TO DASHBOARD</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div style="background: rgba(255, 0, 0, 0.1); color: #ff0000; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="banking-form">
            <form method="POST">
                <div class="form-group">
                    <label>Injection Amount ($)</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <button type="submit" class="submit-btn">EXECUTE CAPITAL INJECTION</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>