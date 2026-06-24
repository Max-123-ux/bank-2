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

// Get current AUM
$portfolioCode = $_SESSION['portfolio_code'];
$result = $conn->query("SELECT pt_aum FROM portfolios WHERE pt_portfolio_code = '$portfolioCode'");
$portfolio = $result->fetch_assoc();
$currentAUM = $portfolio['pt_aum'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } elseif ($amount > $currentAUM) {
        $error = "Insufficient AUM. Current balance: $" . number_format($currentAUM, 2);
    } else {
        $confirmationCode = 'TC-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $updateSql = "UPDATE portfolios SET pt_aum = pt_aum - $amount WHERE pt_portfolio_code = '$portfolioCode'";
        $tradeSql = "INSERT INTO trade_history (th_portfolio_code, th_trade_type, th_amount, th_direction, th_confirmation_code) 
                     VALUES ('$portfolioCode', 'Capital Redemption', $amount, 'outflow', '$confirmationCode')";
        
        if ($conn->query($updateSql) && $conn->query($tradeSql)) {
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
    <title>Capital Redemption - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>CAPITAL REDEMPTION</h1>
            <div class="user-info">
                <span>Available AUM: $<?php echo number_format($currentAUM, 2); ?></span>
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
                    <label>Redemption Amount ($)</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="<?php echo $currentAUM; ?>" required>
                </div>
                <button type="submit" class="submit-btn">EXECUTE CAPITAL REDEMPTION</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>