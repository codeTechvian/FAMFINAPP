-- famfin_db.sql - Family Financial Management Database
-- MySQL 8.0+

-- Create database
CREATE DATABASE IF NOT EXISTS famfin_db;
USE famfin_db;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'assets/images/user-avatar.svg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);

-- --------------------------------------------------------
-- Table: family_members
-- --------------------------------------------------------

CREATE TABLE family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    relationship VARCHAR(50),
    email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_family_user (user_id),
    INDEX idx_family_status (status)
);

-- --------------------------------------------------------
-- Table: income
-- --------------------------------------------------------

CREATE TABLE income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source VARCHAR(50) NOT NULL,
    description TEXT,
    amount DECIMAL(12, 2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_income_user (user_id),
    INDEX idx_income_date (date),
    INDEX idx_income_source (source)
);

-- --------------------------------------------------------
-- Table: expenses
-- --------------------------------------------------------

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('Food', 'Transportation', 'Bills', 'Education', 'Health', 'Shopping', 'Other') NOT NULL,
    description TEXT,
    amount DECIMAL(12, 2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_expense_user (user_id),
    INDEX idx_expense_category (category),
    INDEX idx_expense_date (date)
);

-- --------------------------------------------------------
-- Table: budgets
-- --------------------------------------------------------

CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('Food', 'Transportation', 'Bills', 'Education', 'Health', 'Shopping', 'Other') NOT NULL,
    budget_amount DECIMAL(12, 2) NOT NULL,
    month INT NOT NULL,
    year YEAR NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_budget_user_category (user_id, category, month, year),
    INDEX idx_budget_user (user_id),
    INDEX idx_budget_category (category),
    INDEX idx_budget_month_year (month, year)
);

-- --------------------------------------------------------
-- Table: savings_goals
-- --------------------------------------------------------

CREATE TABLE savings_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(12, 2) NOT NULL,
    current_amount DECIMAL(12, 2) DEFAULT 0,
    target_date DATE,
    description TEXT,
    status ENUM('active', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_goal_user (user_id),
    INDEX idx_goal_status (status)
);

-- --------------------------------------------------------
-- Table: savings_contributions
-- --------------------------------------------------------

CREATE TABLE savings_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_contribution_goal (goal_id),
    INDEX idx_contribution_user (user_id)
);