<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

if ($_SESSION['role'] !== 'bank_officer') {
    header('Location: ../client/portfolio.php');
    exit();
}

$db = PremierDB::getInstance()->getConnection();

$stmt = $db->query("SELECT * FROM account_holders WHERE ah_role = 'client' ORDER BY ah_created_at DESC");
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Directory - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Client Directory</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="officer-panel.php">Dashboard</a>
            <a href="client-directory.php" class="active">Client Directory</a>
            <a href="bank-audit.php">Bank Audit</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <div class="transaction-list">
            <?php foreach ($clients as $client): ?>
            <div class="transaction-item">
                <div class="transaction-info">
                    <h4><?php echo htmlspecialchars($client['ah_fullname']); ?></h4>
                    <p class="meta">
                        IBAN: <?php echo $client['ah_iban']; ?>
                        · Since: <?php echo date('F Y', strtotime($client['ah_created_at'])); ?>
                        <?php if ($client['ah_email']): ?>
                        · <?php echo htmlspecialchars($client['ah_email']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.3rem;">
                        $<?php echo number_format($client['ah_balance'], 2); ?>
                    </div>
                    <p class="meta" style="margin-top: 5px;">
                        Status: <span style="color: <?php echo $client['ah_status'] === 'active' ? 'var(--success-green)' : 'var(--error-red)'; ?>">
                            <?php echo ucfirst($client['ah_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>