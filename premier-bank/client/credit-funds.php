<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

if ($_SESSION['role'] !== 'client') {
    header('Location: ../officer/officer-panel.php');
    exit();
}

$db = PremierDB::getInstance()->getConnection();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    
    if ($amount <= 0) {
        $message = 'Amount must be greater than zero';
        $messageType = 'error';
    } elseif (empty($description)) {
        $message = 'Description is required';
        $messageType = 'error';
    } else {
        try {
            $db->beginTransaction();
            
            // Get current balance
            $stmt = $db->prepare("SELECT ah_balance FROM account_holders WHERE ah_id = ? FOR UPDATE");
            $stmt->execute([$_SESSION['user_id']]);
            $currentBalance = $stmt->fetchColumn();
            
            $newBalance = $currentBalance + $amount;
            $reference = PremierDB::generateReference();
            
            // Update balance
            $stmt = $db->prepare("UPDATE account_holders SET ah_balance = ? WHERE ah_id = ?");
            $stmt->execute([$newBalance, $_SESSION['user_id']]);
            
            // Record transaction
            $stmt = $db->prepare("INSERT INTO transaction_records (tr_reference, tr_account_id, tr_type, tr_amount, tr_balance_before, tr_balance_after, tr_description) VALUES (?, ?, 'credit', ?, ?, ?, ?)");
            $stmt->execute([$reference, $_SESSION['user_id'], $amount, $currentBalance, $newBalance, $description]);
            
            $db->commit();
            $message = "Credit entry of $" . number_format($amount, 2) . " completed successfully. Reference: {$reference}";
            $messageType = 'success';
            
            // Redirect to receipt
            $_SESSION['last_transaction'] = $reference;
            header('Location: bank-receipt.php?ref=' . $reference);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = 'Transaction failed. Please try again.';
            $messageType = 'error';
        }
    }
}

// Get user data for display
$stmt = $db->prepare("SELECT * FROM account_holders WHERE ah_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Entry - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Credit Entry</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="portfolio.php">Portfolio</a>
            <a href="credit-funds.php" class="active">Credit Entry</a>
            <a href="debit-funds.php">Debit Entry</a>
            <a href="send-transfer.php">Transfer</a>
            <a href="standing-order.php">Standing Order</a>
            <a href="account-statement.php">Statement</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 30px;">
            <p style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.2rem;">
                Current Portfolio Balance: 
                <strong>$<?php echo number_format($user['ah_balance'], 2); ?></strong>
            </p>
        </div>
        
        <form method="POST" class="form-elegant">
            <div class="form-group">
                <label for="amount">Credit Amount</label>
                <input type="number" id="amount" name="amount" class="form-input" step="0.01" min="0.01" required placeholder="Enter amount to credit">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-input" rows="3" required placeholder="Source of funds or purpose"></textarea>
            </div>
            
            <button type="submit" class="btn-primary">Process Credit Entry</button>
        </form>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>