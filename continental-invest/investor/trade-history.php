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

$portfolioCode = $_SESSION['portfolio_code'];

// Filter options
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$where = "WHERE th_portfolio_code = '$portfolioCode'";
if ($typeFilter) {
    $where .= " AND th_trade_type = '" . $conn->real_escape_string($typeFilter) . "'";
}

$sql = "SELECT * FROM trade_history $where ORDER BY th_created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade History - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>TRADE HISTORY LOG</h1>
            <div class="user-info">
                <a href="portfolio-view.php" class="logout-btn">BACK TO DASHBOARD</a>
            </div>
        </div>
        
        <div style="background: #2a2a2a; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label style="color: #f7e7ce;">Filter by Type:</label>
                <select name="type" class="form-control" style="width: auto;">
                    <option value="">All Trade Types</option>
                    <option value="Capital Injection" <?php echo $typeFilter == 'Capital Injection' ? 'selected' : ''; ?>>Capital Injection</option>
                    <option value="Capital Redemption" <?php echo $typeFilter == 'Capital Redemption' ? 'selected' : ''; ?>>Capital Redemption</option>
                    <option value="Security Transfer" <?php echo $typeFilter == 'Security Transfer' ? 'selected' : ''; ?>>Security Transfer</option>
                </select>
                <button type="submit" class="action-btn" style="width: auto; margin: 0;">FILTER</button>
            </form>
        </div>
        
        <div style="background: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 8px; padding: 20px;">
            <table class="trade-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Direction</th>
                        <th>Amount</th>
                        <th>Security</th>
                        <th>Settlement Date</th>
                        <th>Confirmation Code</th>
                        <th>Counterparty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($trade = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($trade['th_created_at'])); ?></td>
                        <td><?php echo $trade['th_trade_type']; ?></td>
                        <td>
                            <span class="<?php echo $trade['th_direction'] == 'inflow' ? 'buy-indicator' : 'sell-indicator'; ?>">
                                <?php echo strtoupper($trade['th_direction']); ?>
                            </span>
                        </td>
                        <td>$<?php echo number_format($trade['th_amount'], 2); ?></td>
                        <td><?php echo $trade['th_security_name'] ?: '-'; ?></td>
                        <td><?php echo $trade['th_settlement_date'] ? date('Y-m-d', strtotime($trade['th_settlement_date'])) : '-'; ?></td>
                        <td>
                            <a href="trade-confirm.php?code=<?php echo $trade['th_confirmation_code']; ?>" style="color: #8b0000; text-decoration: none;">
                                <?php echo $trade['th_confirmation_code']; ?>
                            </a>
                        </td>
                        <td><?php echo $trade['th_counterparty'] ?: '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>