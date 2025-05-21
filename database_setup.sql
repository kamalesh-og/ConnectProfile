-- MySQL setup script for GUVI Project

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS user_auth;
USE user_auth;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional: Create test user
-- INSERT INTO users (username, email, password, created_at) 
-- VALUES ('testuser', 'test@example.com', '$2y$10$92IBD0kHNwHycg/FlPHqCOjmVUjFcpr1knrqQaFANc.YUhVfLrhsa', NOW());
-- Note: The password hash above is for 'password123'

-- MongoDB and Redis setup notes
/*
1. MongoDB Setup:
- Make sure MongoDB is installed and running on port 27017
- Create database: use user_profiles
- Create collection: db.createCollection('profiles')

2. Redis Setup:
- Make sure Redis is installed and running on port 6379
- No additional configuration required for this project
*/