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
    $recipientName = trim($_POST['recipient_name']);
    $amount = floatval($_POST['amount']);
    $frequency = $_POST['frequency'];
    $nextDate = $_POST['next_date'];
    
    if (empty($recipientIBAN) || empty($recipientName) || $amount <= 0) {
        $message = 'All fields are required';
        $messageType = 'error';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO standing_orders (so_account_id, so_recipient_iban, so_recipient_name, so_amount, so_frequency, so_next_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $recipientIBAN, $recipientName, $amount, $frequency, $nextDate]);
            
            $message = 'Standing order created successfully';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Failed to create standing order';
            $messageType = 'error';
        }
    }
}

// Get existing standing orders
$stmt = $db->prepare("SELECT * FROM standing_orders WHERE so_account_id = ? AND so_status = 'active' ORDER BY so_next_date ASC");
$stmt->execute([$_SESSION['user_id']]);
$standingOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standing Orders - Premier Commercial Bank</title>
    <link rel="stylesheet" href="../assets/css/luxury.css">
</head>
<body>
    <div class="main-container">
        <header class="bank-header">
            <h1>PREMIER COMMERCIAL BANK</h1>
            <p class="subtitle">Standing Orders</p>
        </header>
        
        <nav class="nav-elegant">
            <a href="portfolio.php">Portfolio</a>
            <a href="credit-funds.php">Credit Entry</a>
            <a href="debit-funds.php">Debit Entry</a>
            <a href="send-transfer.php">Transfer</a>
            <a href="standing-order.php" class="active">Standing Order</a>
            <a href="account-statement.php">Statement</a>
            <a href="../entrance/exit.php">Exit</a>
        </nav>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <h2 style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 2px; margin: 40px 0 20px;">
            Create Standing Order
        </h2>
        
        <form method="POST" class="form-elegant">
            <div class="form-group">
                <label for="recipient_name">Recipient Name</label>
                <input type="text" id="recipient_name" name="recipient_name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="recipient_iban">Recipient IBAN</label>
                <input type="text" id="recipient_iban" name="recipient_iban" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" class="form-input" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="frequency">Frequency</label>
                <select id="frequency" name="frequency" class="form-input" required>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="quarterly">Quarterly</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="next_date">Next Payment Date</label>
                <input type="date" id="next_date" name="next_date" class="form-input" required>
            </div>
            
            <button type="submit" class="btn-primary">Create Standing Order</button>
        </form>
        
        <?php if (!empty($standingOrders)): ?>
        <h2 style="font-family: 'Playfair Display', Georgia, serif; letter-spacing: 2px; margin: 60px 0 20px;">
            Active Standing Orders
        </h2>
        
        <div class="transaction-list">
            <?php foreach ($standingOrders as $so): ?>
            <div class="transaction-item">
                <div class="transaction-info">
                    <h4><?php echo htmlspecialchars($so['so_recipient_name']); ?></h4>
                    <p class="meta">
                        <?php echo ucfirst($so['so_frequency']); ?> · 
                        Next: <?php echo date('F j, Y', strtotime($so['so_next_date'])); ?>
                        <br>
                        IBAN: <?php echo htmlspecialchars($so['so_recipient_iban']); ?>
                    </p>
                </div>
                <div class="transaction-amount debit">
                    $<?php echo number_format($so['so_amount'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="../assets/js/elegant.js"></script>
</body>
</html>