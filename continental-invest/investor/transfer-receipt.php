<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

// Check if logged in and has transfer data
if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_transfer'])) {
    header('Location: portfolio-view.php');
    exit();
}

$transfer = $_SESSION['last_transfer'];
$portfolioCode = $_SESSION['portfolio_code'];

// Get updated balance
$result = $conn->query("SELECT pt_aum FROM portfolios WHERE pt_portfolio_code = '$portfolioCode'");
$portfolio = $result->fetch_assoc();
$newBalance = $portfolio['pt_aum'];

// Clear transfer session data
unset($_SESSION['last_transfer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Receipt - Continental Invest</title>
    <link rel="stylesheet" href="../assets/css/wallstreet.css">
    <style>
        .receipt-container {
            background: white;
            color: black;
            padding: 40px;
            border-radius: 8px;
            max-width: 700px;
            margin: 30px auto;
            font-family: 'Times New Roman', serif;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #8b0000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .receipt-header h1 {
            font-size: 28px;
            color: #212121;
            margin-bottom: 5px;
        }
        .receipt-header .subtitle {
            color: #8b0000;
            font-size: 14px;
            letter-spacing: 2px;
        }
        .receipt-details {
            margin: 30px 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        .receipt-row strong {
            color: #212121;
        }
        .receipt-row .value {
            font-weight: bold;
            color: #8b0000;
        }
        .receipt-amount {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f7e7ce;
            border-radius: 4px;
        }
        .receipt-amount .amount {
            font-size: 36px;
            font-weight: bold;
            color: #212121;
        }
        .receipt-amount .label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .receipt-stamp {
            text-align: center;
            margin-top: 20px;
        }
        .stamp {
            display: inline-block;
            padding: 10px 30px;
            border: 3px solid #8b0000;
            color: #8b0000;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            transform: rotate(-5deg);
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="ticker-tape">
        <div class="ticker-content" id="tickerContent"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="header">
            <h1>TRANSFER CONFIRMATION</h1>
            <div class="user-info">
                <button onclick="window.print()" style="background: #f7e7ce; color: #212121; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    PRINT RECEIPT
                </button>
                <a href="portfolio-view.php" class="logout-btn">BACK TO DASHBOARD</a>
            </div>
        </div>
        
        <div class="receipt-container" id="printableReceipt">
            <div class="receipt-header">
                <h1>CONTINENTAL INVESTMENT BANK</h1>
                <div class="subtitle">FUND TRANSFER CONFIRMATION</div>
            </div>
            
            <div class="receipt-stamp">
                <div class="stamp">TRANSFERRED</div>
            </div>
            
            <div class="receipt-amount">
                <div class="label">Transfer Amount</div>
                <div class="amount">$<?php echo number_format($transfer['amount'], 2); ?></div>
            </div>
            
            <div class="receipt-details">
                <div class="receipt-row">
                    <span><strong>Sender Confirmation:</strong></span>
                    <span class="value"><?php echo $transfer['sender_code']; ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>Recipient Confirmation:</strong></span>
                    <span class="value"><?php echo $transfer['recipient_code']; ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>From Portfolio:</strong></span>
                    <span><?php echo $portfolioCode; ?> (<?php echo $_SESSION['username']; ?>)</span>
                </div>
                <div class="receipt-row">
                    <span><strong>To Portfolio:</strong></span>
                    <span><?php echo $transfer['to_portfolio']; ?> (<?php echo $transfer['to_user']; ?>)</span>
                </div>
                <div class="receipt-row">
                    <span><strong>Transfer Date:</strong></span>
                    <span><?php echo date('F j, Y', strtotime($transfer['date'])); ?></span>
                </div>
                <div class="receipt-row">
                    <span><strong>Transfer Time:</strong></span>
                    <span><?php echo date('g:i:s A', strtotime($transfer['date'])); ?></span>
                </div>
                <?php if ($transfer['description']): ?>
                <div class="receipt-row">
                    <span><strong>Description:</strong></span>
                    <span><?php echo htmlspecialchars($transfer['description']); ?></span>
                </div>
                <?php endif; ?>
                <div class="receipt-row">
                    <span><strong>Transaction Type:</strong></span>
                    <span>Inter-Portfolio Security Transfer</span>
                </div>
                <div class="receipt-row">
                    <span><strong>New Balance:</strong></span>
                    <span class="value">$<?php echo number_format($newBalance, 2); ?></span>
                </div>
            </div>
            
            <div class="receipt-footer">
                <p>This is an official transfer confirmation from Continental Investment Bank.</p>
                <p>Transaction processed and settled immediately.</p>
                <p style="margin-top: 10px;">For any inquiries, please reference the confirmation codes above.</p>
                <p style="margin-top: 15px; font-weight: bold;">© <?php echo date('Y'); ?> Continental Investment Bank. All rights reserved.</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="transfer-funds.php" style="color: var(--champagne); text-decoration: none; padding: 10px 20px; border: 1px solid var(--champagne); border-radius: 4px;">
                MAKE ANOTHER TRANSFER
            </a>
        </div>
    </div>
    
    <script src="../assets/js/ticker.js"></script>
</body>
</html>