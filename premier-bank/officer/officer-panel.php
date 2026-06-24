<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

if ($_SESSION['role'] !== 'bank_officer') {
    header('Location: ../client/portfolio.php');
    exit();
}

$db = PremierDB::getInstance()->getConnection();

// Get statistics
$totalClients = $db->query("SELECT COUNT(*) FROM account_holders WHERE ah_role = 'client'")->fetchColumn();
$totalBalance = $db->query("SELECT SUM(ah_balance) FROM account_holders WHERE ah_role = 'client'")->fetchColumn();
$todayTransactions = $db->query("SELECT COUNT(*) FROM transaction_records WHERE DATE(tr_created_at) = CURDATE()")->fetchColumn();

// Recent transactions
$stmt = $db->query("SELECT tr.*, ah.ah_fullname FROM transaction_records tr JOIN account_holders ah ON tr.tr_account_id = ah.ah_id ORDER BY tr.tr_created_at DESC LIMIT 10");
$recentTransactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Panel - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Officer Panel · <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
        </header>
        
        <nav class="nav-elegant">
            <a href="officer-panel.php" class="active">Dashboard</a>
            <a href="client-directory.php">Client Directory</a>
            <a href="bank-audit.php">Bank Audit</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalClients; ?></div>
                <div class="stat-label">Active Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($totalBalance, 0); ?></div>
                <div class="stat-label">Total Portfolio Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $todayTransactions; ?></div>
                <div class="stat-label">Today's Transactions</div>
            </div>
        </div>
        
        <h2 style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 2px; margin: 40px 0 20px;">
            Recent Transactions
        </h2>
        
        <div class="transaction-list">
            <?php foreach ($recentTransactions as $tr): ?>
            <div class="transaction-item">
                <div class="transaction-info">
                    <h4><?php echo htmlspecialchars($tr['ah_fullname']); ?></h4>
                    <p class="meta">
                        <?php echo date('F j, Y H:i', strtotime($tr['tr_created_at'])); ?>
                        · Ref: <?php echo $tr['tr_reference']; ?>
                        · <?php echo ucfirst(str_replace('_', ' ', $tr['tr_type'])); ?>
                    </p>
                </div>
                <div class="transaction-amount <?php echo in_array($tr['tr_type'], ['credit', 'transfer_in']) ? 'credit' : 'debit'; ?>">
                    $<?php echo number_format($tr['tr_amount'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>