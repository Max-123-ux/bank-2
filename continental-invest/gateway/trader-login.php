<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

require_once '../config/invest_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Check if user exists
    $sql = "SELECT * FROM portfolios WHERE pt_username = '$username' AND pt_status = 'active'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if account is locked
        if ($user['pt_locked_until'] && strtotime($user['pt_locked_until']) > time()) {
            $lockTime = date('H:i:s', strtotime($user['pt_locked_until']));
            header('Location: ../index.php?error=Account locked until ' . $lockTime);
            exit();
        }
        
        // Verify password
        if (password_verify($password, $user['pt_password'])) {
            // Reset failed attempts
            $conn->query("UPDATE portfolios SET pt_failed_attempts = 0, pt_locked_until = NULL WHERE pt_id = {$user['pt_id']}");
            
            // Set session variables
            $_SESSION['user_id'] = $user['pt_id'];
            $_SESSION['username'] = $user['pt_username'];
            $_SESSION['role'] = $user['pt_role'];
            $_SESSION['portfolio_code'] = $user['pt_portfolio_code'];
            $_SESSION['last_activity'] = time();
            
            // Redirect based on role
            if ($user['pt_role'] == 'fund_manager') {
                header('Location: ../fund-manager/manager-desk.php');
            } else {
                header('Location: ../investor/portfolio-view.php');
            }
            exit();
        } else {
            // Increment failed attempts
            $failedAttempts = $user['pt_failed_attempts'] + 1;
            $lockQuery = '';
            
            if ($failedAttempts >= 3) {
                // Lock account for 15 minutes
                $lockTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $lockQuery = ", pt_locked_until = '$lockTime'";
                header('Location: ../index.php?error=Account locked after 3 failed attempts. Try again in 15 minutes.');
            } else {
                header('Location: ../index.php?error=Invalid credentials. ' . (3 - $failedAttempts) . ' attempts remaining.');
            }
            
            $conn->query("UPDATE portfolios SET pt_failed_attempts = $failedAttempts $lockQuery WHERE pt_id = {$user['pt_id']}");
            exit();
        }
    } else {
        header('Location: ../index.php?error=Invalid credentials');
        exit();
    }
}
?>