<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

// Check if fund manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'fund_manager') {
    header('Location: ../index.php');
    exit();
}

// Get all transfers
$transfersSql = "SELECT * FROM trade_history 
    WHERE th_trade_type = 'Security Transfer' 
    AND th_counterparty IS NOT NULL 
    ORDER BY th_created_at DESC";
$transfers = $conn->query($transfersSql);

// Transfer statistics
$statsSql = "SELECT 
    COUNT(*) as total_transfers,
    SUM(th_amount) as total_volume,
    SUM(CASE WHEN th_direction = 'inflow' THEN th_amount ELSE 0 END) as total_received,
    SUM(CASE WHEN th_direction = 'outflow' THEN th_amount ELSE 0 END) as total_sent,
    DATE(MIN(th_created_at)) as first_transfer,
    DATE(MAX(th_created_at)) as last_transfer
FROM trade_history 
WHERE th_trade_type = 'Security Transfer' 
AND th_counterparty IS NOT NULL";
$stats = $conn->query($statsSql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Monitor - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1>TRANSFER MONITOR</h1>
                <span style="color: var(--light-gray); font-size: 12px;">Inter-Portfolio Transfer Oversight</span>
            </div>
            <div class="user-info">
                <a href="manager-desk.php" class="logout-btn">BACK TO DESK</a>
            </div>
        </div>
        
        <!-- Transfer Stats -->
        <div class="manager-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <div class="stat-card">
                <h3>Total Transfers</h3>
                <div class="stat-value"><?php echo number_format($stats['total_transfers']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Volume</h3>
                <div class="stat-value">$<?php echo number_format($stats['total_volume'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Sent</h3>
                <div class="stat-value" style="color: #ff0000;">$<?php echo number_format($stats['total_sent'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Received</h3>
                <div class="stat-value" style="color: #00ff00;">$<?php echo number_format($stats['total_received'] ?? 0, 0); ?></div>
            </div>
        </div>
        
        <!-- All Transfers -->
        <div style="background: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; padding: 20px;">
            <h2 style="color: var(--burgundy); margin-bottom: 20px;">ALL INTER-PORTFOLIO TRANSFERS</h2>
            <table class="trade-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>From Portfolio</th>
                        <th>To Portfolio</th>
                        <th>Amount</th>
                        <th>Sender Confirmation</th>
                        <th>Recipient Confirmation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transfers->num_rows > 0): ?>
                        <?php while($transfer = $transfers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($transfer['th_created_at'])); ?></td>
                            <td>
                                <?php if($transfer['th_direction'] == 'outflow'): ?>
                                    <strong><?php echo $transfer['th_portfolio_code']; ?></strong>
                                <?php else: ?>
                                    <?php echo $transfer['th_counterparty']; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($transfer['th_direction'] == 'inflow'): ?>
                                    <strong><?php echo $transfer['th_portfolio_code']; ?></strong>
                                <?php else: ?>
                                    <?php echo $transfer['th_counterparty']; ?>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format($transfer['th_amount'], 2); ?></td>
                            <td>
                                <?php if($transfer['th_direction'] == 'outflow'): ?>
                                    <span style="color: var(--burgundy);"><?php echo $transfer['th_confirmation_code']; ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($transfer['th_direction'] == 'inflow'): ?>
                                    <span style="color: var(--burgundy);"><?php echo $transfer['th_confirmation_code']; ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="buy-indicator">✓ COMPLETED</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: var(--light-gray);">
                                No inter-portfolio transfers recorded yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>