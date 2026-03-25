CREATE TABLE IF NOT EXISTS portal_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  company VARCHAR(190) NOT NULL,
  role ENUM('client', 'staff', 'admin') NOT NULL DEFAULT 'client',
  status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_users_role (role),
  INDEX idx_portal_users_status (status)
);
