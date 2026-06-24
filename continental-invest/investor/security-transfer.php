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
    $securityName = $conn->real_escape_string($_POST['security_name']);
    $amount = floatval($_POST['amount']);
    $direction = $conn->real_escape_string($_POST['direction']);
    $settlementDate = $conn->real_escape_string($_POST['settlement_date']);
    $counterparty = $conn->real_escape_string($_POST['counterparty']);
    $portfolioCode = $_SESSION['portfolio_code'];
    
    if ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } elseif ($direction == 'outflow') {
        $result = $conn->query("SELECT pt_aum FROM portfolios WHERE pt_portfolio_code = '$portfolioCode'");
        $portfolio = $result->fetch_assoc();
        if ($amount > $portfolio['pt_aum']) {
            $error = "Insufficient AUM for transfer.";
        }
    }
    
    if (!$error) {
        $confirmationCode = 'TC-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        if ($direction == 'inflow') {
            $updateSql = "UPDATE portfolios SET pt_aum = pt_aum + $amount WHERE pt_portfolio_code = '$portfolioCode'";
        } else {
            $updateSql = "UPDATE portfolios SET pt_aum = pt_aum - $amount WHERE pt_portfolio_code = '$portfolioCode'";
        }
        
        $tradeSql = "INSERT INTO trade_history (th_portfolio_code, th_trade_type, th_amount, th_direction, th_security_name, th_settlement_date, th_confirmation_code, th_counterparty) 
                     VALUES ('$portfolioCode', 'Security Transfer', $amount, '$direction', '$securityName', '$settlementDate', '$confirmationCode', '$counterparty')";
        
        if ($conn->query($updateSql) && $conn->query($tradeSql)) {
            header("Location: trade-confirm.php?code=$confirmationCode");
            exit();
        } else {
            $error = "Transfer failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Transfer - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>SECURITY TRANSFER</h1>
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
                    <label>Security Name</label>
                    <input type="text" name="security_name" class="form-control" placeholder="e.g., AAPL, GOOGL, MSFT" required>
                </div>
                <div class="form-group">
                    <label>Transfer Amount ($)</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label>Direction</label>
                    <select name="direction" class="form-control" required>
                        <option value="inflow">Inbound (Receive Securities)</option>
                        <option value="outflow">Outbound (Send Securities)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Settlement Date</label>
                    <input type="date" name="settlement_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Counterparty</label>
                    <input type="text" name="counterparty" class="form-control" placeholder="e.g., Goldman Sachs" required>
                </div>
                <button type="submit" class="submit-btn">EXECUTE SECURITY TRANSFER</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>