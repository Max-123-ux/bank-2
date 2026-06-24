<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

// Get current user's portfolio
$portfolioCode = $_SESSION['portfolio_code'];
$result = $conn->query("SELECT pt_aum FROM portfolios WHERE pt_portfolio_code = '$portfolioCode'");
$portfolio = $result->fetch_assoc();
$currentAUM = $portfolio['pt_aum'];

// Get all active portfolios except current user for transfer
$allPortfolios = $conn->query("SELECT pt_portfolio_code, pt_username, pt_aum FROM portfolios WHERE pt_portfolio_code != '$portfolioCode' AND pt_status = 'active' ORDER BY pt_username");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $toPortfolio = $conn->real_escape_string($_POST['to_portfolio']);
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Validation
    if ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } elseif ($amount > $currentAUM) {
        $error = "Insufficient AUM. Current balance: $" . number_format($currentAUM, 2);
    } elseif ($toPortfolio == $portfolioCode) {
        $error = "Cannot transfer to your own portfolio.";
    } else {
        // Verify recipient exists and is active
        $recipientCheck = $conn->query("SELECT pt_portfolio_code, pt_username FROM portfolios WHERE pt_portfolio_code = '$toPortfolio' AND pt_status = 'active'");
        
        if ($recipientCheck->num_rows == 0) {
            $error = "Invalid recipient portfolio.";
        } else {
            $recipient = $recipientCheck->fetch_assoc();
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Generate confirmation codes
                $senderConfirmation = 'TC-' . date('Ymd') . '-S' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                $recipientConfirmation = 'TC-' . date('Ymd') . '-R' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                // Deduct from sender
                $deductSql = "UPDATE portfolios SET pt_aum = pt_aum - $amount WHERE pt_portfolio_code = '$portfolioCode'";
                
                // Add to recipient
                $addSql = "UPDATE portfolios SET pt_aum = pt_aum + $amount WHERE pt_portfolio_code = '$toPortfolio'";
                
                // Record sender's trade (outflow)
                $senderTradeSql = "INSERT INTO trade_history (th_portfolio_code, th_trade_type, th_amount, th_direction, th_confirmation_code, th_counterparty) 
                                  VALUES ('$portfolioCode', 'Security Transfer', $amount, 'outflow', '$senderConfirmation', '$toPortfolio')";
                
                // Record recipient's trade (inflow)
                $recipientTradeSql = "INSERT INTO trade_history (th_portfolio_code, th_trade_type, th_amount, th_direction, th_confirmation_code, th_counterparty) 
                                     VALUES ('$toPortfolio', 'Security Transfer', $amount, 'inflow', '$recipientConfirmation', '$portfolioCode')";
                
                // Execute all queries
                if ($conn->query($deductSql) && 
                    $conn->query($addSql) && 
                    $conn->query($senderTradeSql) && 
                    $conn->query($recipientTradeSql)) {
                    
                    $conn->commit();
                    
                    // Store transfer details for receipt
                    $_SESSION['last_transfer'] = [
                        'sender_code' => $senderConfirmation,
                        'recipient_code' => $recipientConfirmation,
                        'amount' => $amount,
                        'to_user' => $recipient['pt_username'],
                        'to_portfolio' => $toPortfolio,
                        'date' => date('Y-m-d H:i:s'),
                        'description' => $description
                    ];
                    
                    header("Location: transfer-receipt.php");
                    exit();
                } else {
                    throw new Exception("Transaction failed");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Transfer failed: " . $e->getMessage();
            }
        }
    }
}

// Get recent transfers
$recentTransfers = $conn->query("SELECT * FROM trade_history 
    WHERE th_portfolio_code = '$portfolioCode' 
    AND th_trade_type = 'Security Transfer' 
    AND th_counterparty IS NOT NULL 
    ORDER BY th_created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Funds - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <style>
        .transfer-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .transfer-form {
            background: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            padding: 25px;
        }
        .transfer-history {
            background: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            padding: 25px;
        }
        .recipient-card {
            background: #1a1a1a;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .recipient-card:hover {
            border-color: var(--burgundy);
            background: #252525;
        }
        .recipient-card.selected {
            border-color: var(--burgundy);
            background: rgba(139, 0, 0, 0.1);
        }
        .recipient-name {
            font-weight: bold;
            color: var(--champagne);
            font-size: 16px;
        }
        .recipient-code {
            color: var(--light-gray);
            font-size: 12px;
        }
        .recipient-aum {
            color: var(--burgundy);
            font-size: 14px;
            margin-top: 5px;
        }
        .quick-transfer-btn {
            background: var(--burgundy);
            color: var(--champagne);
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }
        .transfer-status {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .transfer-details {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .transfer-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--medium-gray);
        }
        .contact-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .search-box {
            margin-bottom: 15px;
        }
        .search-box input {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid var(--medium-gray);
            color: var(--champagne);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <div>
                <h1>FUND TRANSFER</h1>
                <span style="color: var(--light-gray); font-size: 12px;">Inter-Portfolio Transfer System</span>
            </div>
            <div class="user-info">
                <span>Available: $<?php echo number_format($currentAUM, 2); ?></span>
                <a href="portfolio-view.php" class="logout-btn">BACK TO DASHBOARD</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div style="background: rgba(255, 0, 0, 0.1); color: #ff0000; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div style="background: rgba(0, 255, 0, 0.1); color: #00ff00; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="transfer-container">
            <!-- Transfer Form -->
            <div class="transfer-form">
                <h2 style="color: var(--burgundy); margin-bottom: 20px;">NEW TRANSFER</h2>
                
                <form method="POST" id="transferForm">
                    <div class="form-group">
                        <label>Transfer Amount ($)</label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" max="<?php echo $currentAUM; ?>" required>
                        <div style="color: var(--light-gray); font-size: 11px; margin-top: 5px;">
                            Maximum: $<?php echo number_format($currentAUM, 2); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Recipient Portfolio Code</label>
                        <input type="text" name="to_portfolio" id="toPortfolio" class="form-control" placeholder="e.g., INV-002" required readonly>
                        <input type="hidden" name="to_portfolio" id="toPortfolioHidden">
                    </div>
                    
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Transfer purpose..."></textarea>
                    </div>
                    
                    <div class="transfer-details" id="transferPreview" style="display: none;">
                        <h3 style="color: var(--champagne); font-size: 14px; margin-bottom: 10px;">TRANSFER PREVIEW</h3>
                        <div class="transfer-detail-row">
                            <span>From:</span>
                            <span><?php echo $portfolioCode; ?> (You)</span>
                        </div>
                        <div class="transfer-detail-row">
                            <span>To:</span>
                            <span id="previewRecipient">-</span>
                        </div>
                        <div class="transfer-detail-row">
                            <span>Amount:</span>
                            <span id="previewAmount">$0.00</span>
                        </div>
                        <div class="transfer-detail-row">
                            <span>Your Balance After:</span>
                            <span id="previewBalance">$<?php echo number_format($currentAUM, 2); ?></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">EXECUTE TRANSFER</button>
                </form>
            </div>
            
            <!-- Recipient Selection -->
            <div class="transfer-history">
                <h2 style="color: var(--burgundy); margin-bottom: 20px;">SELECT RECIPIENT</h2>
                
                <div class="search-box">
                    <input type="text" id="searchRecipient" placeholder="Search by username or portfolio code..." onkeyup="filterRecipients()">
                </div>
                
                <div class="contact-list" id="recipientList">
                    <?php while($p = $allPortfolios->fetch_assoc()): ?>
                    <div class="recipient-card" 
                         onclick="selectRecipient('<?php echo $p['pt_portfolio_code']; ?>', '<?php echo $p['pt_username']; ?>')"
                         data-username="<?php echo strtolower($p['pt_username']); ?>"
                         data-code="<?php echo strtolower($p['pt_portfolio_code']); ?>">
                        <div class="recipient-name"><?php echo htmlspecialchars($p['pt_username']); ?></div>
                        <div class="recipient-code"><?php echo $p['pt_portfolio_code']; ?></div>
                        <div class="recipient-aum">AUM: $<?php echo number_format($p['pt_aum'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($allPortfolios->num_rows == 0): ?>
                    <div style="text-align: center; padding: 30px; color: var(--light-gray);">
                        <p>No other active portfolios available for transfer</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Transfers -->
        <?php if ($recentTransfers->num_rows > 0): ?>
        <div style="background: var(--dark-gray); border: 1px solid var(--medium-gray); border-radius: 8px; padding: 25px; margin-top: 20px;">
            <h2 style="color: var(--burgundy); margin-bottom: 20px;">RECENT TRANSFERS</h2>
            <table class="trade-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Direction</th>
                        <th>Amount</th>
                        <th>Counterparty</th>
                        <th>Confirmation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($transfer = $recentTransfers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($transfer['th_created_at'])); ?></td>
                        <td>
                            <span class="<?php echo $transfer['th_direction'] == 'inflow' ? 'buy-indicator' : 'sell-indicator'; ?>">
                                <?php echo $transfer['th_direction'] == 'inflow' ? '← RECEIVED' : '→ SENT'; ?>
                            </span>
                        </td>
                        <td>$<?php echo number_format($transfer['th_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($transfer['th_counterparty']); ?></td>
                        <td>
                            <a href="trade-confirm.php?code=<?php echo $transfer['th_confirmation_code']; ?>" style="color: var(--burgundy); text-decoration: none;">
                                <?php echo $transfer['th_confirmation_code']; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
    <script>
        let selectedRecipient = null;
        
        function selectRecipient(code, username) {
            // Remove previous selection
            document.querySelectorAll('.recipient-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected');
            
            // Update form
            document.getElementById('toPortfolio').value = code;
            document.getElementById('toPortfolioHidden').value = code;
            document.getElementById('previewRecipient').textContent = username + ' (' + code + ')';
            
            selectedRecipient = {code: code, username: username};
            
            // Show preview
            updatePreview();
        }
        
        function updatePreview() {
            const amount = document.getElementById('amount').value;
            const preview = document.getElementById('transferPreview');
            
            if (amount > 0 && selectedRecipient) {
                preview.style.display = 'block';
                document.getElementById('previewAmount').textContent = '$' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('previewBalance').textContent = '$' + (<?php echo $currentAUM; ?> - parseFloat(amount)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else if (!selectedRecipient) {
                preview.style.display = 'none';
            }
        }
        
        document.getElementById('amount').addEventListener('input', updatePreview);
        
        function filterRecipients() {
            const searchTerm = document.getElementById('searchRecipient').value.toLowerCase();
            const cards = document.querySelectorAll('.recipient-card');
            
            cards.forEach(card => {
                const username = card.getAttribute('data-username');
                const code = card.getAttribute('data-code');
                
                if (username.includes(searchTerm) || code.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Validate before submit
        document.getElementById('transferForm').addEventListener('submit', function(e) {
            if (!selectedRecipient) {
                e.preventDefault();
                alert('Please select a recipient portfolio');
                return false;
            }
            
            const amount = parseFloat(document.getElementById('amount').value);
            if (amount <= 0 || amount > <?php echo $currentAUM; ?>) {
                e.preventDefault();
                alert('Invalid transfer amount');
                return false;
            }
            
            return confirm('Confirm transfer of $' + amount.toLocaleString() + ' to ' + selectedRecipient.username + '?');
        });
    </script>
</body>
</html>