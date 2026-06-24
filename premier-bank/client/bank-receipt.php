<?php
require_once '../config/premier_db.php';
PremierDB::checkSession();

$db = PremierDB::getInstance()->getConnection();

$reference = $_GET['ref'] ?? $_SESSION['last_transaction'] ?? '';

if (empty($reference)) {
    header('Location: portfolio.php');
    exit();
}

// Get transaction
$stmt = $db->prepare("SELECT tr.*, ah.ah_fullname, ah.ah_iban FROM transaction_records tr JOIN account_holders ah ON tr.tr_account_id = ah.ah_id WHERE tr.tr_reference = ? AND tr.tr_account_id = ?");
$stmt->execute([$reference, $_SESSION['user_id']]);
$transaction = $stmt->fetch();

if (!$transaction) {
    header('Location: portfolio.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Receipt - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
    <style>
        @media print {
            body { background: white; }
            .nav-elegant, .btn-primary { display: none; }
            .receipt { box-shadow: none; padding: 40px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Official Receipt</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="portfolio.php">Portfolio</a>
            <a href="account-statement.php">Statement</a>
            <a href="../entrance/exit.php">Exit</a>
            <a href="#" onclick="window.print()" style="margin-left: auto;">Print Receipt</a>
        </nav>
        
        <div class="receipt">
            <div class="receipt-header">
                <div class="bank-name">PREMIER COMMERCIAL BANK</div>
                <p style="margin-top: 5px; color: #888; letter-spacing: 1px;">Est. 2024</p>
                <div style="margin-top: 30px; padding: 20px; border: 1px solid var(--rose-gold-light); display: inline-block;">
                    <p style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.5rem; letter-spacing: 3px; color: var(--rose-gold);">
                        OFFICIAL RECEIPT
                    </p>
                </div>
            </div>
            
            <div class="receipt-details">
                <div class="receipt-row">
                    <span>Reference Number:</span>
                    <strong><?php echo $transaction['tr_reference']; ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Date & Time:</span>
                    <strong><?php echo date('F j, Y - H:i:s', strtotime($transaction['tr_created_at'])); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Account Holder:</span>
                    <strong><?php echo htmlspecialchars($transaction['ah_fullname']); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>IBAN:</span>
                    <strong><?php echo $transaction['ah_iban']; ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Transaction Type:</span>
                    <strong><?php echo ucfirst(str_replace('_', ' ', $transaction['tr_type'])); ?></strong>
                </div>
                
                <?php if ($transaction['tr_related_name']): ?>
                <div class="receipt-row">
                    <span>Related Account:</span>
                    <strong><?php echo htmlspecialchars($transaction['tr_related_name']); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Related IBAN:</span>
                    <strong><?php echo $transaction['tr_related_account']; ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="receipt-row">
                    <span>Description:</span>
                    <strong><?php echo htmlspecialchars($transaction['tr_description']); ?></strong>
                </div>
                
                <div class="receipt-row" style="border-top: 2px solid var(--rose-gold-light); margin-top: 20px; padding-top: 20px;">
                    <span style="font-size: 1.2rem;">Amount:</span>
                    <strong style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.5rem; color: var(--rose-gold);">
                        $<?php echo number_format($transaction['tr_amount'], 2); ?>
                    </strong>
                </div>
                <div class="receipt-row">
                    <span>Balance Before:</span>
                    <strong>$<?php echo number_format($transaction['tr_balance_before'], 2); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Balance After:</span>
                    <strong>$<?php echo number_format($transaction['tr_balance_after'], 2); ?></strong>
                </div>
                <div class="receipt-row">
                    <span>Status:</span>
                    <strong style="color: var(--success-green);"><?php echo ucfirst($transaction['tr_status']); ?></strong>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 60px; padding-top: 30px; border-top: 1px solid var(--marble-dark);">
                <p style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 2px; font-size: 0.9rem;">
                    This is an official receipt from Premier Commercial Bank
                </p>
                <p style="color: #888; font-size: 0.8rem; margin-top: 10px;">
                    For any inquiries, please contact your private banking officer
                </p>
                
                <div style="margin-top: 40px;">
                    <p style="font-family: 'Playfair Display', Georgia, serif; font-style: italic; letter-spacing: 2px;">
                        Authorized Signature
                    </p>
                    <div style="width: 200px; height: 1px; background: var(--midnight-black); margin: 20px auto;"></div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" class="btn-primary">Print Official Receipt</button>
        </div>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>