-- Premier Commercial Banking System
-- Database: liya_db

CREATE DATABASE IF NOT EXISTS liya_db;
USE liya_db;

-- Account Holders Table
CREATE TABLE account_holders (
    ah_id INT AUTO_INCREMENT PRIMARY KEY,
    ah_username VARCHAR(50) UNIQUE NOT NULL,
    ah_password VARCHAR(255) NOT NULL,
    ah_fullname VARCHAR(100) NOT NULL,
    ah_email VARCHAR(100),
    ah_phone VARCHAR(20),
    ah_iban VARCHAR(34) UNIQUE NOT NULL,
    ah_balance DECIMAL(15,2) DEFAULT 0.00,
    ah_role ENUM('client', 'bank_officer') DEFAULT 'client',
    ah_status ENUM('active', 'frozen', 'closed') DEFAULT 'active',
    ah_failed_attempts INT DEFAULT 0,
    ah_locked_until DATETIME NULL,
    ah_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ah_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transaction Records Table
CREATE TABLE transaction_records (
    tr_id INT AUTO_INCREMENT PRIMARY KEY,
    tr_reference VARCHAR(20) UNIQUE NOT NULL,
    tr_account_id INT NOT NULL,
    tr_type ENUM('credit', 'debit', 'transfer_in', 'transfer_out', 'standing_order') NOT NULL,
    tr_amount DECIMAL(15,2) NOT NULL,
    tr_balance_before DECIMAL(15,2) NOT NULL,
    tr_balance_after DECIMAL(15,2) NOT NULL,
    tr_description TEXT,
    tr_related_account VARCHAR(34),
    tr_related_name VARCHAR(100),
    tr_status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
    tr_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tr_account_id) REFERENCES account_holders(ah_id)
);

-- Standing Orders Table
CREATE TABLE standing_orders (
    so_id INT AUTO_INCREMENT PRIMARY KEY,
    so_account_id INT NOT NULL,
    so_recipient_iban VARCHAR(34) NOT NULL,
    so_recipient_name VARCHAR(100) NOT NULL,
    so_amount DECIMAL(15,2) NOT NULL,
    so_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    so_next_date DATE NOT NULL,
    so_status ENUM('active', 'paused', 'cancelled') DEFAULT 'active',
    so_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (so_account_id) REFERENCES account_holders(ah_id)
);

-- Insert Default Bank Officer
INSERT INTO account_holders (ah_username, ah_password, ah_fullname, ah_email, ah_iban, ah_role, ah_status) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Premier Bank Officer', 'officer@premier-bank.com', 'PB0000000000000000000000001', 'bank_officer', 'active');

-- Insert Sample Client
INSERT INTO account_holders (ah_username, ah_password, ah_fullname, ah_email, ah_phone, ah_iban, ah_balance, ah_role) 
VALUES ('john.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'john@email.com', '+1234567890', 'GB29NWBK60161331926819', 50000.00, 'client');

-- Sample Transactions
INSERT INTO transaction_records (tr_reference, tr_account_id, tr_type, tr_amount, tr_balance_before, tr_balance_after, tr_description, tr_related_name)
VALUES 
('TRX-001-2024', 2, 'credit', 25000.00, 0.00, 25000.00, 'Initial Portfolio Deposit', 'John Smith'),
('TRX-002-2024', 2, 'credit', 25000.00, 25000.00, 50000.00, 'Quarterly Investment Return', 'Investment Portfolio');

-- Create indexes for better performance
CREATE INDEX idx_transactions_account ON transaction_records(tr_account_id);
CREATE INDEX idx_transactions_date ON transaction_records(tr_created_at);
CREATE INDEX idx_standing_orders_account ON standing_orders(so_account_id);