<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

if ($_SESSION['role'] !== 'client') {
    header('Location: ../officer/officer-panel.php');
    exit();
}

$db = PremierDB::getInstance()->getConnection();

// Filter parameters
$fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$toDate = $_GET['to'] ?? date('Y-m-d');

// Get transactions
$stmt = $db->prepare("SELECT * FROM transaction_records WHERE tr_account_id = ? AND DATE(tr_created_at) BETWEEN ? AND ? ORDER BY tr_created_at DESC");
$stmt->execute([$_SESSION['user_id'], $fromDate, $toDate]);
$transactions = $stmt->fetchAll();

// Get account summary
$stmt = $db->prepare("SELECT * FROM account_holders WHERE ah_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement of Account - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Statement of Account</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="portfolio.php">Portfolio</a>
            <a href="credit-funds.php">Credit Entry</a>
            <a href="debit-funds.php">Debit Entry</a>
            <a href="send-transfer.php">Transfer</a>
            <a href="standing-order.php">Standing Order</a>
            <a href="account-statement.php" class="active">Statement</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <div class="receipt" style="margin: 30px 0;">
            <div class="receipt-header">
                <div class="bank-name">PREMIER COMMERCIAL BANK</div>
                <p style="margin-top: 10px; letter-spacing: 2px;">STATEMENT OF ACCOUNT</p>
            </div>
            
            <div class="receipt-details">
                <div class="receipt-row">
                    <span>Account Holder:</span>
                    <strong><?php echo htmlspecialchars($user['ah_fullname']); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>IBAN:</span>
                    <strong><?php echo $user['ah_iban']; ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Period:</span>
                    <strong><?php echo date('F j, Y', strtotime($fromDate)); ?> - <?php echo date('F j, Y', strtotime($toDate)); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Current Balance:</span>
                    <strong>$<?php echo number_format($user['ah_balance'], 2); ?></strong>
                </div>
            </div>
        </div>
        
        <form method="GET" class="form-elegant" style="margin-bottom: 40px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: end;">
                <div class="form-group">
                    <label for="from">From Date</label>
                    <input type="date" id="from" name="from" class="form-input" value="<?php echo $fromDate; ?>">
                </div>
                <div class="form-group">
                    <label for="to">To Date</label>
                    <input type="date" id="to" name="to" class="form-input" value="<?php echo $toDate; ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-primary" style="padding: 15px 30px;">Filter</button>
                </div>
            </div>
        </form>
        
        <div class="transaction-list">
            <?php foreach ($transactions as $tr): ?>
            <div class="transaction-item">
                <div class="transaction-info">
                    <h4><?php echo htmlspecialchars($tr['tr_description']); ?></h4>
                    <p class="meta">
                        <?php echo date('F j, Y H:i', strtotime($tr['tr_created_at'])); ?>
                        · Ref: <?php echo $tr['tr_reference']; ?>
                        · <?php echo ucfirst(str_replace('_', ' ', $tr['tr_type'])); ?>
                        <?php if ($tr['tr_related_name']): ?>
                        · <?php echo htmlspecialchars($tr['tr_related_name']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div class="transaction-amount <?php echo in_array($tr['tr_type'], ['credit', 'transfer_in']) ? 'credit' : 'debit'; ?>">
                        <?php echo in_array($tr['tr_type'], ['credit', 'transfer_in']) ? '+' : '-'; ?>
                        $<?php echo number_format($tr['tr_amount'], 2); ?>
                    </div>
                    <p class="meta" style="margin-top: 5px;">
                        Balance: $<?php echo number_format($tr['tr_balance_after'], 2); ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($transactions)): ?>
            <p style="text-align: center; color: #888; font-style: italic; padding: 40px;">
                No transactions found for this period
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>