
CREATE DATABASE IF NOT EXISTS hospital_booking;
USE hospital_booking;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient')
        NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. DOCTORS TABLE
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    qualification VARCHAR(100),
    specialization VARCHAR(100),
    experience INT DEFAULT 0,
    status ENUM('Active', 'Inactive')
        DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- 3. APPOINTMENTS TABLE
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT,
    status ENUM(
        'Pending',
        'Confirmed',
        'Completed',
        'Rejected',
        'Cancelled'
    ) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_doctor_slot (
        doctor_id,
        appointment_date,
        appointment_time
    ),
    FOREIGN KEY (patient_id)
        REFERENCES users(user_id),
    FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id)
);