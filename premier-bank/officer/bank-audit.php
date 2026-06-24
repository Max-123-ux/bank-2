<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

if ($_SESSION['role'] !== 'bank_officer') {
    header('Location: ../client/portfolio.php');
    exit();
}

$db = PremierDB::getInstance()->getConnection();

// Get all transactions for audit
$stmt = $db->query("SELECT tr.*, ah.ah_fullname, ah.ah_iban FROM transaction_records tr JOIN account_holders ah ON tr.tr_account_id = ah.ah_id ORDER BY tr.tr_created_at DESC LIMIT 50");
$transactions = $stmt->fetchAll();

// Calculate totals
$totalCredits = $db->query("SELECT SUM(tr_amount) FROM transaction_records WHERE tr_type IN ('credit', 'transfer_in')")->fetchColumn();
$totalDebits = $db->query("SELECT SUM(tr_amount) FROM transaction_records WHERE tr_type IN ('debit', 'transfer_out')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Audit - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Bank Audit Log</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="officer-panel.php">Dashboard</a>
            <a href="client-directory.php">Client Directory</a>
            <a href="bank-audit.php" class="active">Bank Audit</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($totalCredits, 0); ?></div>
                <div class="stat-label">Total Credits</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($totalDebits, 0); ?></div>
                <div class="stat-label">Total Debits</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($totalCredits - $totalDebits, 0); ?></div>
                <div class="stat-label">Net Flow</div>
            </div>
        </div>
        
        <h2 style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 2px; margin: 40px 0 20px;">
            Transaction Audit Log
        </h2>
        
        <div class="transaction-list">
            <?php foreach ($transactions as $tr): ?>
            <div class="transaction-item">
                <div class="transaction-info">
                    <h4><?php echo htmlspecialchars($tr['ah_fullname']); ?></h4>
                    <p class="meta">
                        Ref: <?php echo $tr['tr_reference']; ?>
                        · <?php echo date('Y-m-d H:i:s', strtotime($tr['tr_created_at'])); ?>
                        · <?php echo ucfirst(str_replace('_', ' ', $tr['tr_type'])); ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div class="transaction-amount <?php echo in_array($tr['tr_type'], ['credit', 'transfer_in']) ? 'credit' : 'debit'; ?>">
                        $<?php echo number_format($tr['tr_amount'], 2); ?>
                    </div>
                    <p class="meta" style="margin-top: 5px;">
                        IBAN: <?php echo substr($tr['ah_iban'], 0, 10); ?>...
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>