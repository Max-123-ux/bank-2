<?php
// Premier Bank Database Configuration
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'liya_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BANK_NAME', 'PREMIER COMMERCIAL BANK');
define('SESSION_TIMEOUT', 60); // 60 seconds
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

class PremierDB {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Session Management
    public static function checkSession() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /premier-bank/index.php');
            exit();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            header('Location: /premier-bank/index.php?timeout=1');
            exit();
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    // Generate unique reference number
    public static function generateReference() {
        return 'TRX-' . strtoupper(substr(uniqid(), -8)) . '-' . date('Y');
    }
    
    // Generate IBAN
    public static function generateIBAN() {
        return 'PB' . str_pad(mt_rand(0, 999999999999999999), 28, '0', STR_PAD_LEFT);
    }
}

// Initialize session timeout
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}
?>