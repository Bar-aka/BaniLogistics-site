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

CREATE TABLE IF NOT EXISTS portal_shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_email VARCHAR(190) NOT NULL,
  reference VARCHAR(40) NOT NULL UNIQUE,
  client_name VARCHAR(190) NOT NULL,
  origin VARCHAR(190) NOT NULL,
  destination VARCHAR(190) NOT NULL,
  mode VARCHAR(80) NOT NULL,
  status VARCHAR(80) NOT NULL,
  next_step VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_shipments_client_email (client_email),
  INDEX idx_portal_shipments_status (status)
);

CREATE TABLE IF NOT EXISTS portal_quotes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_email VARCHAR(190) NOT NULL,
  quote_number VARCHAR(40) NOT NULL UNIQUE,
  client_name VARCHAR(190) NOT NULL,
  shipment_type VARCHAR(80) NOT NULL,
  origin VARCHAR(190) NOT NULL,
  destination VARCHAR(190) NOT NULL,
  mode VARCHAR(80) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency VARCHAR(12) NOT NULL DEFAULT 'KES',
  status VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_quotes_client_email (client_email),
  INDEX idx_portal_quotes_status (status)
);

CREATE TABLE IF NOT EXISTS portal_invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_email VARCHAR(190) NOT NULL,
  invoice_number VARCHAR(40) NOT NULL UNIQUE,
  client_name VARCHAR(190) NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency VARCHAR(12) NOT NULL DEFAULT 'KES',
  status VARCHAR(80) NOT NULL,
  due_date DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_invoices_client_email (client_email),
  INDEX idx_portal_invoices_status (status)
);
