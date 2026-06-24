-- Create database
CREATE DATABASE IF NOT EXISTS sofi_db;
USE sofi_db;

-- Portfolios table
CREATE TABLE IF NOT EXISTS portfolios (
    pt_id INT AUTO_INCREMENT PRIMARY KEY,
    pt_username VARCHAR(50) UNIQUE NOT NULL,
    pt_password VARCHAR(255) NOT NULL,
    pt_role ENUM('investor', 'fund_manager') NOT NULL DEFAULT 'investor',
    pt_aum DECIMAL(15,2) DEFAULT 0.00,
    pt_portfolio_code VARCHAR(20) UNIQUE NOT NULL,
    pt_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pt_last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    pt_status ENUM('active', 'inactive') DEFAULT 'active',
    pt_failed_attempts INT DEFAULT 0,
    pt_locked_until TIMESTAMP NULL
);

-- Trade history table
CREATE TABLE IF NOT EXISTS trade_history (
    th_id INT AUTO_INCREMENT PRIMARY KEY,
    th_portfolio_code VARCHAR(20) NOT NULL,
    th_trade_type ENUM('Capital Injection', 'Capital Redemption', 'Security Transfer') NOT NULL,
    th_amount DECIMAL(15,2) NOT NULL,
    th_direction ENUM('inflow', 'outflow') NOT NULL,
    th_security_name VARCHAR(100) NULL,
    th_settlement_date DATE NULL,
    th_confirmation_code VARCHAR(50) UNIQUE NOT NULL,
    th_counterparty VARCHAR(50) NULL,
    th_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_portfolio (th_portfolio_code),
    INDEX idx_trade_type (th_trade_type)
);

-- Insert default fund manager (password: admin)
INSERT INTO portfolios (pt_username, pt_password, pt_role, pt_aum, pt_portfolio_code) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'fund_manager', 10000000.00, 'FM-101');

-- Insert sample investors
INSERT INTO portfolios (pt_username, pt_password, pt_role, pt_aum, pt_portfolio_code) VALUES
('john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'investor', 250000.00, 'INV-001'),
('jane_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'investor', 500000.00, 'INV-002'),
('robert_chen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'investor', 750000.00, 'INV-003');

-- Insert sample trade history
INSERT INTO trade_history (th_portfolio_code, th_trade_type, th_amount, th_direction, th_security_name, th_settlement_date, th_confirmation_code, th_counterparty) VALUES
('INV-001', 'Capital Injection', 50000.00, 'inflow', NULL, '2024-01-15', 'TC-20240115-001', 'Bank of America'),
('INV-001', 'Security Transfer', 25000.00, 'inflow', 'AAPL', '2024-01-20', 'TC-20240120-001', 'Goldman Sachs'),
('INV-002', 'Capital Injection', 100000.00, 'inflow', NULL, '2024-01-10', 'TC-20240110-001', 'Chase'),
('INV-003', 'Capital Redemption', 50000.00, 'outflow', NULL, '2024-01-25', 'TC-20240125-001', 'Wells Fargo');