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

// Get all portfolios statistics
$totalAUM = 0;
$totalInvestors = 0;
$totalTrades = 0;

// Total AUM
$aumResult = $conn->query("SELECT SUM(pt_aum) as total_aum FROM portfolios WHERE pt_role = 'investor' AND pt_status = 'active'");
$aumData = $aumResult->fetch_assoc();
$totalAUM = $aumData['total_aum'] ?? 0;

// Total Investors
$invResult = $conn->query("SELECT COUNT(*) as count FROM portfolios WHERE pt_role = 'investor' AND pt_status = 'active'");
$invData = $invResult->fetch_assoc();
$totalInvestors = $invData['count'];

// Total Trades Today
$today = date('Y-m-d');
$tradeResult = $conn->query("SELECT COUNT(*) as count FROM trade_history WHERE DATE(th_created_at) = '$today'");
$tradeData = $tradeResult->fetch_assoc();
$totalTrades = $tradeData['count'];

// Recent trades across all portfolios
$recentTrades = $conn->query("SELECT * FROM trade_history ORDER BY th_created_at DESC LIMIT 10");

// Daily trade volume
$volumeResult = $conn->query("SELECT SUM(th_amount) as daily_volume FROM trade_history WHERE DATE(th_created_at) = '$today'");
$volumeData = $volumeResult->fetch_assoc();
$dailyVolume = $volumeData['daily_volume'] ?? 0;

// Previous day AUM for comparison
$yesterdayTotal = $totalAUM * 0.97; // Mock previous day
$percentageChange = (($totalAUM - $yesterdayTotal) / $yesterdayTotal) * 100;

// Portfolio breakdown
$portfolioBreakdown = $conn->query("SELECT * FROM portfolios WHERE pt_status = 'active' ORDER BY pt_aum DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Manager Desk - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .manager-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            color: var(--light-gray);
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--champagne);
        }
        .stat-card .stat-change {
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        .portfolio-list {
            background: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .manager-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1>FUND MANAGER DESK</h1>
                <span style="color: var(--light-gray); font-size: 12px;">FM-101 | <?php echo $_SESSION['username']; ?></span>
            </div>
            <div class="user-info">
                <span id="idleCountdown" style="color: #8b0000; font-size: 12px;"></span>
                <a href="../gateway/logout.php" class="logout-btn">SECURE LOGOUT</a>
            </div>
        </div>
        
        <div class="manager-actions">
    <a href="../gateway/new-portfolio.php" class="action-btn" style="width: auto; padding: 10px 20px;">+ NEW PORTFOLIO</a>
    <a href="investor-roster.php" class="action-btn" style="width: auto; padding: 10px 20px;">INVESTOR ROSTER</a>
    <a href="fund-audit.php" class="action-btn" style="width: auto; padding: 10px 20px;">FUND AUDIT</a>
    <a href="transfer-monitor.php" class="action-btn" style="width: auto; padding: 10px 20px;">TRANSFER MONITOR</a>
</div>
        
        <!-- Stats Overview -->
        <div class="manager-stats">
            <div class="stat-card">
                <h3>Total AUM</h3>
                <div class="stat-value">$<?php echo number_format($totalAUM, 0); ?></div>
                <span class="stat-change <?php echo $percentageChange >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($percentageChange >= 0 ? '↑' : '↓') . number_format(abs($percentageChange), 2); ?>% from yesterday
                </span>
            </div>
            <div class="stat-card">
                <h3>Active Investors</h3>
                <div class="stat-value"><?php echo $totalInvestors; ?></div>
                <span class="stat-change" style="color: var(--light-gray);">Portfolios under management</span>
            </div>
            <div class="stat-card">
                <h3>Today's Trades</h3>
                <div class="stat-value"><?php echo $totalTrades; ?></div>
                <span class="stat-change" style="color: var(--light-gray);">Trade tickets executed</span>
            </div>
            <div class="stat-card">
                <h3>Daily Volume</h3>
                <div class="stat-value">$<?php echo number_format($dailyVolume, 0); ?></div>
                <span class="stat-change" style="color: var(--light-gray);">Total transaction volume</span>
            </div>
        </div>
        
        <!-- 4-Quadrant Grid -->
        <div class="grid-container">
            <!-- Quadrant 1: Portfolio Breakdown -->
            <div class="grid-item">
                <h2>PORTFOLIO BREAKDOWN</h2>
                <table class="trade-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Username</th>
                            <th>AUM</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($portfolio = $portfolioBreakdown->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $portfolio['pt_portfolio_code']; ?></td>
                            <td><?php echo $portfolio['pt_username']; ?></td>
                            <td>$<?php echo number_format($portfolio['pt_aum'], 2); ?></td>
                            <td>
                                <span class="<?php echo $portfolio['pt_status'] == 'active' ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    <?php echo strtoupper($portfolio['pt_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Quadrant 2: AUM Distribution Chart -->
            <div class="grid-item">
                <h2>AUM DISTRIBUTION</h2>
                <canvas id="aumChart"></canvas>
            </div>
            
            <!-- Quadrant 3: Recent Trade Activity -->
            <div class="grid-item">
                <h2>RECENT TRADE TICKETS</h2>
                <table class="trade-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Portfolio</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($trade = $recentTrades->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($trade['th_created_at'])); ?></td>
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
            
            <!-- Quadrant 4: Market Overview -->
            <div class="grid-item">
                <h2>MARKET OVERVIEW</h2>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--medium-gray);">
                        <span>S&P 500</span>
                        <span>
                            4,783.45
                            <span class="buy-indicator">↑2.3%</span>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--medium-gray);">
                        <span>NASDAQ</span>
                        <span>
                            15,123.78
                            <span class="buy-indicator">↑1.8%</span>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--medium-gray);">
                        <span>DJIA</span>
                        <span>
                            37,592.98
                            <span class="buy-indicator">↑0.7%</span>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--medium-gray);">
                        <span>VIX</span>
                        <span>
                            14.32
                            <span class="sell-indicator">↓5.2%</span>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span>US 10Y Yield</span>
                        <span>4.15%</span>
                    </div>
                </div>
                <div style="text-align: center; color: var(--light-gray); font-size: 12px; margin-top: 15px;">
                    Last Updated: <?php echo date('Y-m-d H:i:s'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
    <script>
        // AUM Distribution Chart
        const ctx = document.getElementById('aumChart').getContext('2d');
        
        <?php
        // Reset pointer for portfolio breakdown
        $portfolioBreakdown->data_seek(0);
        $labels = [];
        $data = [];
        while($portfolio = $portfolioBreakdown->fetch_assoc()) {
            $labels[] = $portfolio['pt_portfolio_code'];
            $data[] = $portfolio['pt_aum'];
        }
        ?>
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'AUM by Portfolio',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: '#8b0000',
                    borderColor: '#f7e7ce',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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