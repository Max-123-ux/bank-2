<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['code'])) {
    header('Location: portfolio-view.php');
    exit();
}

$confirmationCode = $conn->real_escape_string($_GET['code']);
$sql = "SELECT * FROM trade_history WHERE th_confirmation_code = '$confirmationCode' AND th_portfolio_code = '{$_SESSION['portfolio_code']}'";
$result = $conn->query($sql);
$trade = $result->fetch_assoc();

if (!$trade) {
    header('Location: portfolio-view.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Confirmation - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>TRADE CONFIRMATION</h1>
            <div class="user-info">
                <a href="portfolio-view.php" class="logout-btn">BACK TO DASHBOARD</a>
                <button onclick="window.print()" style="background: #f7e7ce; color: #212121; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                    PRINT RECEIPT
                </button>
            </div>
        </div>
        
        <div class="receipt-container">
            <div class="receipt-header">
                <h2 style="font-size: 24px;">CONTINENTAL INVESTMENT BANK</h2>
                <p style="font-size: 14px; margin-top: 5px;">Trade Confirmation Receipt</p>
                <p style="font-size: 12px; color: #666;"><?php echo date('F j, Y, g:i a'); ?></p>
            </div>
            
            <div class="receipt-details">
                <div class="receipt-row">
                    <span><strong>Confirmation Code:</strong></span>
                    <span><?php echo $trade['th_confirmation_code']; ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>Portfolio Code:</strong></span>
                    <span><?php echo $trade['th_portfolio_code']; ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>Trade Type:</strong></span>
                    <span><?php echo $trade['th_trade_type']; ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>Amount:</strong></span>
                    <span>$<?php echo number_format($trade['th_amount'], 2); ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>Direction:</strong></span>
                    <span><?php echo ucfirst($trade['th_direction']); ?></span>
                </div>
                <?php if ($trade['th_security_name']): ?>
                <div class="receipt-row">
                    <span><strong>Security:</strong></span>
                    <span><?php echo $trade['th_security_name']; ?></span>
                </div>
                <?php endif; ?>
                <?php if ($trade['th_settlement_date']): ?>
                <div class="receipt-row">
                    <span><strong>Settlement Date:</strong></span>
                    <span><?php echo date('F j, Y', strtotime($trade['th_settlement_date'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($trade['th_counterparty']): ?>
                <div class="receipt-row">
                    <span><strong>Counterparty:</strong></span>
                    <span><?php echo $trade['th_counterparty']; ?></span>
                </div>
                <?php endif; ?>
                <div class="receipt-row">
                    <span><strong>Transaction Date:</strong></span>
                    <span><?php echo date('F j, Y', strtotime($trade['th_created_at'])); ?></span>
                </div>
            </div>
            
            <div class="receipt-footer">
                <p>This is an official trade confirmation from Continental Investment Bank.</p>
                <p style="margin-top: 10px; font-size: 10px;">© <?php echo date('Y'); ?> Continental Investment Bank. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>