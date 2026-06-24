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

// Date filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Audit statistics
$auditSql = "SELECT 
    th_trade_type,
    COUNT(*) as trade_count,
    SUM(th_amount) as total_amount,
    SUM(CASE WHEN th_direction = 'inflow' THEN th_amount ELSE 0 END) as total_inflow,
    SUM(CASE WHEN th_direction = 'outflow' THEN th_amount ELSE 0 END) as total_outflow
FROM trade_history 
WHERE DATE(th_created_at) BETWEEN '$startDate' AND '$endDate'
GROUP BY th_trade_type";

$auditResult = $conn->query($auditSql);

// Daily summary
$dailySql = "SELECT 
    DATE(th_created_at) as trade_date,
    COUNT(*) as trade_count,
    SUM(th_amount) as daily_volume,
    SUM(CASE WHEN th_direction = 'inflow' THEN th_amount ELSE 0 END) as inflow,
    SUM(CASE WHEN th_direction = 'outflow' THEN th_amount ELSE 0 END) as outflow
FROM trade_history 
WHERE DATE(th_created_at) BETWEEN '$startDate' AND '$endDate'
GROUP BY DATE(th_created_at)
ORDER BY trade_date DESC";

$dailyResult = $conn->query($dailySql);

// Net flow calculation
$netFlowSql = "SELECT 
    SUM(CASE WHEN th_direction = 'inflow' THEN th_amount ELSE -th_amount END) as net_flow
FROM trade_history 
WHERE DATE(th_created_at) BETWEEN '$startDate' AND '$endDate'";

$netFlowResult = $conn->query($netFlowSql);
$netFlow = $netFlowResult->fetch_assoc()['net_flow'] ?? 0;

// Largest trades
$largestTrades = $conn->query("SELECT * FROM trade_history 
    WHERE DATE(th_created_at) BETWEEN '$startDate' AND '$endDate'
    ORDER BY th_amount DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Audit - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1>FUND AUDIT REPORT</h1>
                <span style="color: var(--light-gray); font-size: 12px;">Comprehensive Transaction Analysis</span>
            </div>
            <div class="user-info">
                <a href="manager-desk.php" class="logout-btn">BACK TO DESK</a>
            </div>
        </div>
        
        <!-- Date Filter -->
        <div style="background: var(--dark-gray); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                <div class="form-group" style="margin: 0;">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <button type="submit" class="action-btn" style="width: auto; margin: 0; padding: 10px 20px; margin-top: 20px;">APPLY FILTER</button>
            </form>
        </div>
        
        <!-- Key Metrics -->
        <div class="manager-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <div class="stat-card">
                <h3>Net Flow</h3>
                <div class="stat-value" style="color: <?php echo $netFlow >= 0 ? '#00ff00' : '#ff0000'; ?>">
                    $<?php echo number_format(abs($netFlow), 0); ?>
                </div>
                <span class="stat-change"><?php echo $netFlow >= 0 ? 'Net Inflow' : 'Net Outflow'; ?></span>
            </div>
            <div class="stat-card">
                <h3>Total Volume</h3>
                <div class="stat-value">
                    $<?php 
                        $volumeSql = "SELECT SUM(th_amount) as vol FROM trade_history WHERE DATE(th_created_at) BETWEEN '$startDate' AND '$endDate'";
                        $volResult = $conn->query($volumeSql);
                        echo number_format($volResult->fetch_assoc()['vol'] ?? 0, 0);
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Total Trades</h3>
                <div class="stat-value">
                    <?php 
                        $countSql = "SELECT COUNT(*) as cnt FROM trade_history WHERE DATE(th_created_at) BETWEEN '$startDate' AND '$endDate'";
                        $cntResult = $conn->query($countSql);
                        echo number_format($cntResult->fetch_assoc()['cnt'] ?? 0);
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Period</h3>
                <div class="stat-value" style="font-size: 16px;">
                    <?php echo date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate)); ?>
                </div>
            </div>
        </div>
        
        <div class="grid-container">
            <!-- Trade Type Summary -->
            <div class="grid-item">
                <h2>TRADE TYPE SUMMARY</h2>
                <table class="trade-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Count</th>
                            <th>Inflow</th>
                            <th>Outflow</th>
                            <th>Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalInflow = 0;
                        $totalOutflow = 0;
                        while($audit = $auditResult->fetch_assoc()): 
                            $net = $audit['total_inflow'] - $audit['total_outflow'];
                            $totalInflow += $audit['total_inflow'];
                            $totalOutflow += $audit['total_outflow'];
                        ?>
                        <tr>
                            <td><?php echo $audit['th_trade_type']; ?></td>
                            <td><?php echo $audit['trade_count']; ?></td>
                            <td class="buy-indicator">$<?php echo number_format($audit['total_inflow'], 2); ?></td>
                            <td class="sell-indicator">$<?php echo number_format($audit['total_outflow'], 2); ?></td>
                            <td>
                                <span class="<?php echo $net >= 0 ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    $<?php echo number_format(abs($net), 2); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <tr style="font-weight: bold; border-top: 2px solid var(--burgundy);">
                            <td>TOTAL</td>
                            <td></td>
                            <td class="buy-indicator">$<?php echo number_format($totalInflow, 2); ?></td>
                            <td class="sell-indicator">$<?php echo number_format($totalOutflow, 2); ?></td>
                            <td>
                                <span class="<?php echo ($totalInflow - $totalOutflow) >= 0 ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    $<?php echo number_format(abs($totalInflow - $totalOutflow), 2); ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Daily Volume Chart -->
            <div class="grid-item">
                <h2>DAILY VOLUME TREND</h2>
                <canvas id="volumeChart"></canvas>
            </div>
            
            <!-- Largest Trades -->
            <div class="grid-item">
                <h2>LARGEST TRADE TICKETS</h2>
                <table class="trade-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Portfolio</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($trade = $largestTrades->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($trade['th_created_at'])); ?></td>
                            <td><?php echo $trade['th_portfolio_code']; ?></td>
                            <td><?php echo $trade['th_trade_type']; ?></td>
                            <td>
                                <span class="<?php echo $trade['th_direction'] == 'inflow' ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    $<?php echo number_format($trade['th_amount'], 2); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Daily Breakdown -->
            <div class="grid-item">
                <h2>DAILY BREAKDOWN</h2>
                <table class="trade-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Trades</th>
                            <th>Volume</th>
                            <th>Net Flow</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($daily = $dailyResult->fetch_assoc()): 
                            $dailyNet = $daily['inflow'] - $daily['outflow'];
                        ?>
                        <tr>
                            <td><?php echo date('M d', strtotime($daily['trade_date'])); ?></td>
                            <td><?php echo $daily['trade_count']; ?></td>
                            <td>$<?php echo number_format($daily['daily_volume'], 2); ?></td>
                            <td>
                                <span class="<?php echo $dailyNet >= 0 ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    $<?php echo number_format(abs($dailyNet), 2); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
    <script>
        // Daily Volume Chart
        const ctx = document.getElementById('volumeChart').getContext('2d');
        
        <?php
        $dailyResult->data_seek(0);
        $dates = [];
        $volumes = [];
        while($daily = $dailyResult->fetch_assoc()) {
            $dates[] = date('M d', strtotime($daily['trade_date']));
            $volumes[] = $daily['daily_volume'];
        }
        $dates = array_reverse($dates);
        $volumes = array_reverse($volumes);
        ?>
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Daily Volume',
                    data: <?php echo json_encode($volumes); ?>,
                    borderColor: '#8b0000',
                    backgroundColor: 'rgba(139, 0, 0, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#f7e7ce',
                    pointBorderColor: '#8b0000',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#f7e7ce'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#f7e7ce',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: '#3d3d3d'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#f7e7ce'
                        },
                        grid: {
                            color: '#3d3d3d'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>