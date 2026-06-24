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
    $recipientIBAN = trim($_POST['recipient_iban']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    
    if (empty($recipientIBAN)) {
        $message = 'Recipient IBAN is required';
        $messageType = 'error';
    } elseif ($recipientIBAN === $_SESSION['iban']) {
        $message = 'Cannot transfer to your own account';
        $messageType = 'error';
    } elseif ($amount <= 0) {
        $message = 'Amount must be greater than zero';
        $messageType = 'error';
    } else {
        try {
            $db->beginTransaction();
            
            // Get sender balance
            $stmt = $db->prepare("SELECT ah_balance, ah_fullname FROM account_holders WHERE ah_id = ? FOR UPDATE");
            $stmt->execute([$_SESSION['user_id']]);
            $sender = $stmt->fetch();
            
            if ($sender['ah_balance'] < $amount) {
                throw new Exception('Insufficient funds');
            }
            
            // Get recipient
            $stmt = $db->prepare("SELECT ah_id, ah_balance, ah_fullname FROM account_holders WHERE ah_iban = ? FOR UPDATE");
            $stmt->execute([$recipientIBAN]);
            $recipient = $stmt->fetch();
            
            if (!$recipient) {
                throw new Exception('Recipient account not found');
            }
            
            $reference = PremierDB::generateReference();
            $senderNewBalance = $sender['ah_balance'] - $amount;
            $recipientNewBalance = $recipient['ah_balance'] + $amount;
            
            // Update sender
            $stmt = $db->prepare("UPDATE account_holders SET ah_balance = ? WHERE ah_id = ?");
            $stmt->execute([$senderNewBalance, $_SESSION['user_id']]);
            
            // Update recipient
            $stmt = $db->prepare("UPDATE account_holders SET ah_balance = ? WHERE ah_id = ?");
            $stmt->execute([$recipientNewBalance, $recipient['ah_id']]);
            
            // Record sender transaction
            $stmt = $db->prepare("INSERT INTO transaction_records (tr_reference, tr_account_id, tr_type, tr_amount, tr_balance_before, tr_balance_after, tr_description, tr_related_account, tr_related_name) VALUES (?, ?, 'transfer_out', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$reference, $_SESSION['user_id'], $amount, $sender['ah_balance'], $senderNewBalance, $description, $recipientIBAN, $recipient['ah_fullname']]);
            
            // Record recipient transaction
            $stmt = $db->prepare("INSERT INTO transaction_records (tr_reference, tr_account_id, tr_type, tr_amount, tr_balance_before, tr_balance_after, tr_description, tr_related_account, tr_related_name) VALUES (?, ?, 'transfer_in', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$reference . '-IN', $recipient['ah_id'], $amount, $recipient['ah_balance'], $recipientNewBalance, "Transfer from {$_SESSION['fullname']}", $_SESSION['iban'], $sender['ah_fullname']]);
            
            $db->commit();
            
            $_SESSION['last_transaction'] = $reference;
            header('Location: bank-receipt.php?ref=' . $reference);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

$stmt = $db->prepare("SELECT * FROM account_holders WHERE ah_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Instruction - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Transfer Instruction</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="portfolio.php">Portfolio</a>
            <a href="credit-funds.php">Credit Entry</a>
            <a href="debit-funds.php">Debit Entry</a>
            <a href="send-transfer.php" class="active">Transfer</a>
            <a href="standing-order.php">Standing Order</a>
            <a href="account-statement.php">Statement</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 30px;">
            <p style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.2rem;">
                Your IBAN: <strong><?php echo $_SESSION['iban']; ?></strong>
            </p>
            <p>Available Balance: <strong>$<?php echo number_format($user['ah_balance'], 2); ?></strong></p>
        </div>
        
        <form method="POST" class="form-elegant">
            <div class="form-group">
                <label for="recipient_iban">Recipient IBAN</label>
                <input type="text" id="recipient_iban" name="recipient_iban" class="form-input" required placeholder="Enter recipient's IBAN">
            </div>
            
            <div class="form-group">
                <label for="amount">Transfer Amount</label>
                <input type="number" id="amount" name="amount" class="form-input" step="0.01" min="0.01" required placeholder="Enter amount to transfer">
            </div>
            
            <div class="form-group">
                <label for="description">Reference / Description</label>
                <textarea id="description" name="description" class="form-input" rows="3" required placeholder="Purpose of transfer"></textarea>
            </div>
            
            <button type="submit" class="btn-primary">Execute Transfer</button>
        </form>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>