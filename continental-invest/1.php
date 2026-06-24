<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'sofi_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Continental Invest - Admin Reset Tool</h2>";

// Check if admin exists
$checkSql = "SELECT * FROM portfolios WHERE pt_username = 'admin'";
$result = $conn->query($checkSql);

if ($result->num_rows > 0) {
    // Update existing admin
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
    $updateSql = "UPDATE portfolios SET 
        pt_password = '$hashedPassword',
        pt_role = 'fund_manager',
        pt_portfolio_code = 'FM-101',
        pt_status = 'active',
        pt_failed_attempts = 0,
        pt_locked_until = NULL,
        pt_aum = 10000000.00
    WHERE pt_username = 'admin'";
    
    if ($conn->query($updateSql)) {
        echo "<p style='color: green;'>✓ Admin account updated successfully!</p>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin</strong></p>";
        echo "<p>Portfolio Code: <strong>FM-101</strong></p>";
        echo "<p>Role: <strong>Fund Manager</strong></p>";
        echo "<p>AUM: <strong>$10,000,000.00</strong></p>";
        echo "<p>Status: <strong>Active (unlocked)</strong></p>";
    } else {
        echo "<p style='color: red;'>Error updating admin: " . $conn->error . "</p>";
    }
} else {
    // Create new admin
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
    $insertSql = "INSERT INTO portfolios (pt_username, pt_password, pt_role, pt_aum, pt_portfolio_code, pt_status) 
                  VALUES ('admin', '$hashedPassword', 'fund_manager', 10000000.00, 'FM-101', 'active')";
    
    if ($conn->query($insertSql)) {
        echo "<p style='color: green;'>✓ Admin account created successfully!</p>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin</strong></p>";
        echo "<p>Portfolio Code: <strong>FM-101</strong></p>";
        echo "<p>Role: <strong>Fund Manager</strong></p>";
        echo "<p>AUM: <strong>$10,000,000.00</strong></p>";
    } else {
        echo "<p style='color: red;'>Error creating admin: " . $conn->error . "</p>";
    }
}

// Also reset sample investors
$investors = [
    ['username' => 'john_doe', 'code' => 'INV-001', 'aum' => 250000.00],
    ['username' => 'jane_smith', 'code' => 'INV-002', 'aum' => 500000.00],
    ['username' => 'robert_chen', 'code' => 'INV-003', 'aum' => 750000.00]
];

echo "<h3>Sample Investor Accounts:</h3>";

foreach ($investors as $investor) {
    $checkInvestor = "SELECT * FROM portfolios WHERE pt_username = '{$investor['username']}'";
    $invResult = $conn->query($checkInvestor);
    
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
    
    if ($invResult->num_rows > 0) {
        $updateInv = "UPDATE portfolios SET 
            pt_password = '$hashedPassword',
            pt_role = 'investor',
            pt_portfolio_code = '{$investor['code']}',
            pt_status = 'active',
            pt_failed_attempts = 0,
            pt_locked_until = NULL,
            pt_aum = {$investor['aum']}
        WHERE pt_username = '{$investor['username']}'";
        $conn->query($updateInv);
    } else {
        $insertInv = "INSERT INTO portfolios (pt_username, pt_password, pt_role, pt_aum, pt_portfolio_code) 
                      VALUES ('{$investor['username']}', '$hashedPassword', 'investor', {$investor['aum']}, '{$investor['code']}')";
        $conn->query($insertInv);
    }
    
    echo "<p>✓ {$investor['username']} / admin (Code: {$investor['code']}, AUM: $" . number_format($investor['aum'], 2) . ")</p>";
}

echo "<hr>";
echo "<p><strong>All accounts have been reset successfully!</strong></p>";
echo "<p><a href='index.php' style='color: #8b0000; text-decoration: none; font-weight: bold;'>← Go to Login Page</a></p>";
echo "<p style='color: #666; font-size: 12px;'>For security, delete this file after use.</p>";

$conn->close();
?>