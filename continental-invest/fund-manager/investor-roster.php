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

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$where = "WHERE pt_role = 'investor'";
if ($search) {
    $where .= " AND (pt_username LIKE '%$search%' OR pt_portfolio_code LIKE '%$search%')";
}
if ($statusFilter) {
    $where .= " AND pt_status = '$statusFilter'";
}

// Get investors
$sql = "SELECT * FROM portfolios $where ORDER BY pt_aum DESC";
$investors = $conn->query($sql);

// Get summary statistics
$statsSql = "SELECT 
    COUNT(*) as total_investors,
    SUM(pt_aum) as total_aum,
    AVG(pt_aum) as avg_aum,
    MAX(pt_aum) as max_aum,
    MIN(pt_aum) as min_aum
FROM portfolios WHERE pt_role = 'investor' AND pt_status = 'active'";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Roster - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1>INVESTOR ROSTER</h1>
                <span style="color: var(--light-gray); font-size: 12px;">Portfolio Management Overview</span>
            </div>
            <div class="user-info">
                <a href="manager-desk.php" class="logout-btn">BACK TO DESK</a>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="manager-stats" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 20px;">
            <div class="stat-card">
                <h3>Total Investors</h3>
                <div class="stat-value"><?php echo number_format($stats['total_investors']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total AUM</h3>
                <div class="stat-value">$<?php echo number_format($stats['total_aum'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Average AUM</h3>
                <div class="stat-value">$<?php echo number_format($stats['avg_aum'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Largest Portfolio</h3>
                <div class="stat-value">$<?php echo number_format($stats['max_aum'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Smallest Portfolio</h3>
                <div class="stat-value">$<?php echo number_format($stats['min_aum'] ?? 0, 0); ?></div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div style="background: var(--dark-gray); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <input type="text" name="search" class="form-control" placeholder="Search by username or portfolio code..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="action-btn" style="width: auto; margin: 0; padding: 10px 20px;">SEARCH</button>
                <?php if($search || $statusFilter): ?>
                    <a href="investor-roster.php" class="action-btn" style="width: auto; margin: 0; padding: 10px 20px; background: var(--medium-gray);">CLEAR</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Investor Table -->
        <div style="background: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; padding: 20px;">
            <table class="trade-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Portfolio Code</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>AUM</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Last Activity</th>
                        <th>Failed Attempts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($investors->num_rows > 0): ?>
                        <?php while($investor = $investors->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $investor['pt_portfolio_code']; ?></strong></td>
                            <td><?php echo $investor['pt_username']; ?></td>
                            <td><?php echo $investor['pt_role']; ?></td>
                            <td>$<?php echo number_format($investor['pt_aum'], 2); ?></td>
                            <td>
                                <span class="<?php echo $investor['pt_status'] == 'active' ? 'buy-indicator' : 'sell-indicator'; ?>">
                                    <?php echo strtoupper($investor['pt_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($investor['pt_created_at'])); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($investor['pt_last_activity'])); ?></td>
                            <td><?php echo $investor['pt_failed_attempts']; ?>/3</td>
                            <td>
                                <a href="investor-detail.php?code=<?php echo $investor['pt_portfolio_code']; ?>" style="color: var(--burgundy); text-decoration: none; font-size: 12px;">
                                    VIEW DETAILS
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: var(--light-gray);">
                                No investors found matching your criteria
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php
        // Calculate AUM distribution tiers
        $tierQuery = "SELECT 
            CASE 
                WHEN pt_aum >= 1000000 THEN 'Premium (>$1M)'
                WHEN pt_aum >= 500000 THEN 'High ($500K-$1M)'
                WHEN pt_aum >= 250000 THEN 'Mid ($250K-$500K)'
                WHEN pt_aum >= 100000 THEN 'Standard ($100K-$250K)'
                ELSE 'Entry (<$100K)'
            END as tier,
            COUNT(*) as count,
            SUM(pt_aum) as total_aum
        FROM portfolios 
        WHERE pt_role = 'investor' AND pt_status = 'active'
        GROUP BY tier
        ORDER BY total_aum DESC";
        $tierResult = $conn->query($tierQuery);
        ?>
        
        <!-- AUM Tiers -->
        <div style="background: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; padding: 20px; margin-top: 20px;">
            <h2 style="color: var(--burgundy); margin-bottom: 15px;">AUM TIER DISTRIBUTION</h2>
            <table class="trade-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Tier</th>
                        <th>Investors</th>
                        <th>Total AUM</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = $stats['total_aum'] ?? 0;
                    while($tier = $tierResult->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $tier['tier']; ?></td>
                        <td><?php echo $tier['count']; ?></td>
                        <td>$<?php echo number_format($tier['total_aum'], 2); ?></td>
                        <td><?php echo $grandTotal > 0 ? number_format(($tier['total_aum'] / $grandTotal) * 100, 1) : '0'; ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>