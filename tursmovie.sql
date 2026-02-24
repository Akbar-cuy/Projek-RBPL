CREATE DATABASE IF NOT EXISTS tursmovie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tursmovie;

CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, username VARCHAR(50) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(100), phone VARCHAR(20), location VARCHAR(100), role ENUM('customer','kasir','chef','operator') NOT NULL DEFAULT 'customer', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

INSERT INTO users (name,username,password,email,phone,location,role) VALUES
('Budi Santoso','customer','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer@tursmovie.com','+62 812-3456-7890','Jakarta, Indonesia','customer'),
('Siti Aminah','kasir','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kasir@tursmovie.com','+62 813-0000-0001','Jakarta, Indonesia','kasir'),
('Agus Wijaya','chef','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','chef@tursmovie.com','+62 813-0000-0002','Jakarta, Indonesia','chef'),
('Rini Kusuma','operator','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','operator@tursmovie.com','+62 813-0000-0003','Jakarta, Indonesia','operator');