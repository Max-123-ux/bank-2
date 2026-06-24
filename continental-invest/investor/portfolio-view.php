<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get portfolio data
$portfolioCode = $_SESSION['portfolio_code'];
$sql = "SELECT * FROM portfolios WHERE pt_portfolio_code = '$portfolioCode'";
$result = $conn->query($sql);
$portfolio = $result->fetch_assoc();

// Calculate percentage change (mock data)
// Fixed: Handle zero AUM case
if ($portfolio['pt_aum'] > 0) {
    $previousAUM = $portfolio['pt_aum'] * 0.95; // Simulated previous value
    $percentageChange = (($portfolio['pt_aum'] - $previousAUM) / $previousAUM) * 100;
} else {
    $previousAUM = 0;
    $percentageChange = 0;
}

// Get recent trades
$tradesSql = "SELECT * FROM trade_history WHERE th_portfolio_code = '$portfolioCode' ORDER BY th_created_at DESC LIMIT 5";
$tradesResult = $conn->query($tradesSql);

// Get asset allocation (mock data)
// Fixed: Handle cases where AUM might be 0
if ($portfolio['pt_aum'] > 0) {
    $assetAllocation = [
        ['name' => 'Equities', 'value' => $portfolio['pt_aum'] * 0.45],
        ['name' => 'Fixed Income', 'value' => $portfolio['pt_aum'] * 0.25],
        ['name' => 'Cash', 'value' => $portfolio['pt_aum'] * 0.15],
        ['name' => 'Alternatives', 'value' => $portfolio['pt_aum'] * 0.10],
        ['name' => 'Real Estate', 'value' => $portfolio['pt_aum'] * 0.05]
    ];
} else {
    // Default allocation for zero AUM
    $assetAllocation = [
        ['name' => 'Equities', 'value' => 0],
        ['name' => 'Fixed Income', 'value' => 0],
        ['name' => 'Cash', 'value' => 0],
        ['name' => 'Alternatives', 'value' => 0],
        ['name' => 'Real Estate', 'value' => 0]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Dashboard - <?php echo htmlspecialchars($portfolioCode); ?></title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>PORTFOLIO TERMINAL</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($portfolioCode); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span id="idleCountdown" style="color: #8b0000; font-size: 12px;"></span>
                <a href="../gateway/logout.php" class="logout-btn">LOGOUT</a>
            </div>
        </div>
        
        <div class="grid-container">
            <!-- Quadrant 1: Balance Overview -->
            <div class="grid-item">
                <h2>ASSETS UNDER MANAGEMENT</h2>
                <div class="balance-amount">
                    $<?php echo number_format($portfolio['pt_aum'], 2); ?> AUM
                    <?php if ($portfolio['pt_aum'] > 0): ?>
                    <span class="balance-change <?php echo $percentageChange >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($percentageChange >= 0 ? '↑' : '↓') . number_format(abs($percentageChange), 2); ?>%
                    </span>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 20px; font-size: 12px; color: #666;">
                    Market Index: S&P 500 4,783.45 | NASDAQ 15,123.78
                </div>
            </div>
            
            <!-- Quadrant 2: Asset Allocation Chart -->
            <div class="grid-item">
                <h2>ASSET ALLOCATION</h2>
                <?php if ($portfolio['pt_aum'] > 0): ?>
                <canvas id="allocationChart" style="max-height: 250px;"></canvas>
                <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #666;">
                    <p>No assets allocated yet</p>
                    <p style="font-size: 12px; margin-top: 10px;">Execute a Capital Injection to begin building your portfolio</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Quadrant 3: Quick Actions -->
<div class="grid-item">
    <h2>TRADE TICKETS</h2>
    <div class="action-grid">
        <a href="inject-capital.php" class="action-btn">CAPITAL INJECTION</a>
        <a href="redeem-capital.php" class="action-btn">CAPITAL REDEMPTION</a>
        <a href="security-transfer.php" class="action-btn">SECURITY TRANSFER</a>
        <a href="transfer-funds.php" class="action-btn">FUND TRANSFER</a>
        <a href="trade-history.php" class="action-btn" style="grid-column: span 2;">TRADE HISTORY</a>
    </div>
</div>
            
            <!-- Quadrant 4: Recent Trades -->
            <div class="grid-item">
                <h2>RECENT TRADE TICKETS</h2>
                <?php if ($tradesResult->num_rows > 0): ?>
                <table class="trade-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Direction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($trade = $tradesResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($trade['th_created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($trade['th_trade_type']); ?></td>
                            <td>$<?php echo number_format($trade['th_amount'], 2); ?></td>
                            <td>
                                <span class="<?php echo $trade['th_direction'] == 'inflow' ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    <?php echo strtoupper($trade['th_direction']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 30px; color: #666;">
                    <p>No trade history available</p>
                    <p style="font-size: 12px; margin-top: 10px;">Your trade tickets will appear here</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
    <?php if ($portfolio['pt_aum'] > 0): ?>
    <script>
        // Asset Allocation Pie Chart
        const ctx = document.getElementById('allocationChart').getContext('2d');
        const allocationData = <?php echo json_encode(array_column($assetAllocation, 'value')); ?>;
        const allocationLabels = <?php echo json_encode(array_column($assetAllocation, 'name')); ?>;
        
        // Only show non-zero allocations
        const filteredData = [];
        const filteredLabels = [];
        const backgroundColors = ['#8b0000', '#f7e7ce', '#2a2a2a', '#3d3d3d', '#666666'];
        const filteredColors = [];
        
        allocationData.forEach((value, index) => {
            if (value > 0) {
                filteredData.push(value);
                filteredLabels.push(allocationLabels[index]);
                filteredColors.push(backgroundColors[index]);
            }
        });
        
        const allocationChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: filteredLabels.length > 0 ? filteredLabels : allocationLabels,
                datasets: [{
                    data: filteredData.length > 0 ? filteredData : [1], // Prevent empty chart
                    backgroundColor: filteredColors.length > 0 ? filteredColors : ['#3d3d3d'],
                    borderColor: '#212121',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#f7e7ce',
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>