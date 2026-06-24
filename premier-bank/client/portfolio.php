<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

if ($_SESSION['role'] !== 'client') {
    header('Location: ../officer/officer-panel.php');
    exit();
}

$db = PremierDB::getInstance()->getConnection();

// Get user data
$stmt = $db->prepare("SELECT * FROM account_holders WHERE ah_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get recent transactions
$stmt = $db->prepare("SELECT * FROM transaction_records WHERE tr_account_id = ? ORDER BY tr_created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

// Calculate portfolio health percentage (mock)
$portfolioHealth = min(100, ($user['ah_balance'] / 100000) * 100);
$circumference = 2 * M_PI * 100;
$offset = $circumference - ($portfolioHealth / 100) * $circumference;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Balance - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Private Portfolio</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="portfolio.php" class="active">Portfolio</a>
            <a href="credit-funds.php">Credit Entry</a>
            <a href="debit-funds.php">Debit Entry</a>
            <a href="send-transfer.php">Transfer</a>
            <a href="standing-order.php">Standing Order</a>
            <a href="account-statement.php">Statement</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <div class="balance-display fade-in">
            <div class="circular-progress">
                <svg width="250" height="250">
                    <circle class="bg-circle" cx="125" cy="125" r="100"></circle>
                    <circle class="progress-circle" cx="125" cy="125" r="100"
                            stroke-dasharray="<?php echo $circumference; ?>"
                            stroke-dashoffset="<?php echo $offset; ?>">
                    </circle>
                </svg>
                <div class="balance-amount" data-amount="<?php echo $user['ah_balance']; ?>">
                    $<?php echo number_format($user['ah_balance'], 2); ?>
                </div>
            </div>
            <div class="balance-label">Portfolio Balance</div>
        </div>
        
        <div class="action-links">
            <a href="credit-funds.php" class="action-link">
                <span class="icon">↓</span> Credit Entry
            </a>
            <a href="debit-funds.php" class="action-link">
                <span class="icon">↑</span> Debit Entry
            </a>
            <a href="send-transfer.php" class="action-link">
                <span class="icon">↗</span> Transfer Instruction
            </a>
        </div>
        
        <div style="margin-top: 60px;">
            <h3 style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 2px; margin-bottom: 30px;">
                Recent Activity
            </h3>
            
            <div class="transaction-list">
                <?php foreach ($transactions as $tr): ?>
                <div class="transaction-item fade-in">
                    <div class="transaction-info">
                        <h4><?php echo htmlspecialchars($tr['tr_description']); ?></h4>
                        <p class="meta">
                            <?php echo date('F j, Y - H:i', strtotime($tr['tr_created_at'])); ?> 
                            · Ref: <?php echo $tr['tr_reference']; ?>
                        </p>
                    </div>
                    <div class="transaction-amount <?php echo in_array($tr['tr_type'], ['credit', 'transfer_in']) ? 'credit' : 'debit'; ?>">
                        <?php echo in_array($tr['tr_type'], ['credit', 'transfer_in']) ? '+' : '-'; ?>
                        $<?php echo number_format($tr['tr_amount'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($transactions)): ?>
                <p style="text-align: center; color: #888; font-style: italic; padding: 40px;">
                    No transactions recorded
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
    <script>
        // Animate circular progress
        setTimeout(() => {
            updateCircularProgress(<?php echo $portfolioHealth; ?>);
        }, 500);
    </script>
</body>
</html>