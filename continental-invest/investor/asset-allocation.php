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
$result = $conn->query("SELECT pt_aum FROM portfolios WHERE pt_portfolio_code = '$portfolioCode'");
$portfolio = $result->fetch_assoc();

// Mock asset allocation data
$assetAllocation = [
    ['name' => 'Equities', 'value' => $portfolio['pt_aum'] * 0.45, 'color' => '#8b0000'],
    ['name' => 'Fixed Income', 'value' => $portfolio['pt_aum'] * 0.25, 'color' => '#f7e7ce'],
    ['name' => 'Cash', 'value' => $portfolio['pt_aum'] * 0.15, 'color' => '#2a2a2a'],
    ['name' => 'Alternatives', 'value' => $portfolio['pt_aum'] * 0.10, 'color' => '#3d3d3d'],
    ['name' => 'Real Estate', 'value' => $portfolio['pt_aum'] * 0.05, 'color' => '#666666']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Allocation - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>ASSET ALLOCATION ANALYSIS</h1>
            <div class="user-info">
                <a href="portfolio-view.php" class="logout-btn">BACK TO DASHBOARD</a>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="grid-item">
                <h2>ALLOCATION CHART</h2>
                <canvas id="allocationChart"></canvas>
            </div>
            
            <div class="grid-item">
                <h2>ALLOCATION BREAKDOWN</h2>
                <table class="trade-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Asset Class</th>
                            <th>Value</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assetAllocation as $asset): ?>
                        <tr>
                            <td><?php echo $asset['name']; ?></td>
                            <td>$<?php echo number_format($asset['value'], 2); ?></td>
                            <td><?php echo number_format(($asset['value'] / $portfolio['pt_aum']) * 100, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight: bold; border-top: 2px solid #8b0000;">
                            <td>Total AUM</td>
                            <td>$<?php echo number_format($portfolio['pt_aum'], 2); ?></td>
                            <td>100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
    <script>
        const ctx = document.getElementById('allocationChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($assetAllocation, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($assetAllocation, 'value')); ?>,
                    backgroundColor: <?php echo json_encode(array_column($assetAllocation, 'color')); ?>,
                    borderColor: '#212121',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#f7e7ce',
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>