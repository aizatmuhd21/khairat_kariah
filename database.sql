-- Create database
CREATE DATABASE IF NOT EXISTS khairat_kariah;
USE khairat_kariah;

-- Table: kariah (locations)
CREATE TABLE kariah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kariah VARCHAR(100) NOT NULL,
    lokasi TEXT,
    nombor_kecemasan VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: users (regular members)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_ahli VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    no_kp VARCHAR(14) UNIQUE,
    email VARCHAR(100) UNIQUE,
    telefon VARCHAR(15),
    kariah_id INT,
    jumlah_ahli_keluarga INT DEFAULT 1,
    password VARCHAR(255),
    status ENUM('aktif', 'tidak_aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kariah_id) REFERENCES kariah(id)
);

-- Table: admins
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    nama VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tahun YEAR,
    jumlah DECIMAL(10,2),
    status ENUM('lunas', 'belum_bayar') DEFAULT 'belum_bayar',
    tarikh_bayar DATE,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_tahun (user_id, tahun)
);

-- Insert sample data
INSERT INTO kariah (nama_kariah, lokasi, nombor_kecemasan) VALUES
('Taman Mawar', 'No. 48, Jalan Mawar, Taman Mawar', '019-123 4567'),
('Bandar Baru', 'No. 12, Jalan Baru, Bandar Baru', '012-345 6789'),
('Bukit Palma', 'No. 78, Jalan Palma, Bukit Palma', '017-890 1234'),
('Taman Sri Gombak', 'No. 23, Jalan Gombak, Taman Sri Gombak', '013-456 7890'),
('Desa Petaling', 'No. 56, Jalan Petaling, Desa Petaling', '014-567 8901'),
('Keramat', 'No. 90, Jalan Keramat, Keramat', '016-678 9012');

-- Insert sample admin
INSERT INTO admins (username, nama, email, password, role) VALUES
('admin', 'Admin Utama', 'admin@khairat.com', '$2y$10$YourHashedPasswordHere', 'super_admin');

-- Insert sample users
INSERT INTO users (no_ahli, nama, no_kp, email, telefon, kariah_id, jumlah_ahli_keluarga, password) VALUES
('A001284', 'Ahmad bin Ali', '900101-10-1234', 'ahmad@email.com', '019-123 4567', 1, 5, '$2y$10$YourHashedPasswordHere'),
('A001285', 'Siti binti Tan', '910202-08-5678', 'siti@email.com', '012-345 6789', 2, 3, '$2y$10$YourHashedPasswordHere'),
('A001286', 'Mohd Faiz', '920303-14-9012', 'faiz@email.com', '017-890 1234', 3, 4, '$2y$10$YourHashedPasswordHere');

-- Insert sample payments
INSERT INTO payments (user_id, tahun, jumlah, status, tarikh_bayar) VALUES
(1, 2024, 50.00, 'lunas', '2024-01-15'),
(1, 2025, 50.00, 'lunas', '2025-01-10'),
(1, 2026, 50.00, 'belum_bayar', NULL),
(2, 2024, 50.00, 'lunas', '2024-02-20'),
(2, 2025, 50.00, 'lunas', '2025-02-15'),
(2, 2026, 50.00, 'lunas', '2026-01-05'),
(3, 2024, 50.00, 'lunas', '2024-03-10'),
(3, 2025, 50.00, 'lunas', '2025-03-12'),
(3, 2026, 50.00, 'belum_bayar', NULL);