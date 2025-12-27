-- Smart Masjid schema (development)
CREATE DATABASE IF NOT EXISTS abbkfute_sma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE abbkfute_sma;

CREATE TABLE IF NOT EXISTS users (
  user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) UNIQUE,
  address VARCHAR(255) NOT NULL,
  date_of_birth DATE NULL,
  role ENUM('USER','ADMIN','SUPER') NOT NULL,
  password_hash VARCHAR(255) NULL,
  mosque_id BIGINT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS otp_codes (
  phone VARCHAR(20) PRIMARY KEY,
  otp_code VARCHAR(6),
  expires_at DATETIME
);

CREATE TABLE IF NOT EXISTS oauth_refresh_tokens (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  device_id VARCHAR(100),
  refresh_token VARCHAR(255),
  expires_at DATETIME,
  revoked BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token_user (user_id)
);

CREATE TABLE IF NOT EXISTS user_locations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  latitude DECIMAL(9,6),
  longitude DECIMAL(9,6),
  logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_location_user (user_id)
);

CREATE TABLE IF NOT EXISTS mosques (
  mosque_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150),
  address VARCHAR(255),
  latitude DECIMAL(9,6),
  longitude DECIMAL(9,6),
  radius INT DEFAULT 80,
  silent_after_minutes INT DEFAULT 20,
  is_active BOOLEAN DEFAULT 1
);

CREATE TABLE IF NOT EXISTS jamaah_times (
  mosque_id BIGINT PRIMARY KEY,
  fajr TIME,
  zuhr TIME,
  asr TIME,
  maghrib TIME,
  isha TIME,
  jummah TIME
);

CREATE TABLE IF NOT EXISTS mosque_daily_prayer_times (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  mosque_id BIGINT NOT NULL,
  prayer_date DATE NOT NULL,
  fajr TIME,
  zuhr TIME,
  asr TIME,
  maghrib TIME,
  isha TIME,
  UNIQUE KEY mosque_day (mosque_id, prayer_date)
);

-- Seed super admin (change password for production)
INSERT INTO users (name, phone, address, role, password_hash) VALUES
('Arif', '+8801000000000', 'Dhaka', 'SUPER', '$2y$10$c5nJX8DyPpVNnVvF/HXtSOZX31lrT7xvFeoJEBhDigw1k3DybZ0n6')
ON DUPLICATE KEY UPDATE name = VALUES(name);
