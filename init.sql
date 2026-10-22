-- 1. Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS contact_db;
USE contact_db;

-- 2. Users table (Task B: Login, Task A: Forgot Password)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255) DEFAULT NULL
);

-- 3. Contacts table (Task C: Entry Form, Task D: Search)
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    mobile_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Default test user: username admin | password password123
INSERT INTO users (username, email, password)
VALUES ('admin', 'admin@kabarak.ac.ke',
'$2y$10$AWhBFSwj.4obH7OBLpZmyeTSMRofJ5U4i2HvfSq3EXh/hTpAr3grC')
ON DUPLICATE KEY UPDATE id=id;
