-- HAMROFUTSAL DATABASE SCHEMA
-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS hamrofutsal;
USE hamrofutsal;

-- 1. Users Table
-- Stores credentials and roles for both Players and Owners
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Will store secure hashed passwords
    role ENUM('player', 'owner') NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_sent_at TIMESTAMP NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    token_expiry DATETIME DEFAULT NULL,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_login TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Owner Details Table
-- Stores additional business profile information for Futsal Owners
CREATE TABLE IF NOT EXISTS owner_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNIQUE NOT NULL,
    business_name VARCHAR(100) DEFAULT NULL,
    pan_number VARCHAR(50) DEFAULT NULL,
    bank_details TEXT DEFAULT NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Futsal Grounds Table
-- Stores information about futsal courts managed by owners
CREATE TABLE IF NOT EXISTS futsals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    price_per_hour DECIMAL(10, 2) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) DEFAULT 'assets/images/default_futsal.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Reward Points Table
-- Tracks loyalty points accrued by players
CREATE TABLE IF NOT EXISTS reward_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT UNIQUE NOT NULL,
    points INT DEFAULT 100, -- 100 points as signup bonus
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bookings Table
-- Stores futsal slot bookings made by players
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    futsal_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL, -- e.g., '07:00:00'
    end_time TIME NOT NULL,   -- e.g., '08:00:00' (normally start_time + 1 hour)
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    reward_points_modified INT DEFAULT 0, -- Keeps track of points gained/lost for this booking (0 = not processed, 1 = processed)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (futsal_id) REFERENCES futsals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Reviews Table
-- Stores user feedback, star ratings, and review messages for each futsal ground
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    futsal_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (futsal_id) REFERENCES futsals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
