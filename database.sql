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
  INDEX idx_portal_users_phone (phone),
  INDEX idx_portal_users_role (role),
  INDEX idx_portal_users_status (status)
);

CREATE TABLE IF NOT EXISTS portal_shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_email VARCHAR(190) NOT NULL,
  reference VARCHAR(40) NOT NULL UNIQUE,
  client_name VARCHAR(190) NOT NULL,
  incoming_request_id INT UNSIGNED NULL,
  assigned_to VARCHAR(190) NULL,
  assigned_name VARCHAR(190) NULL,
  origin VARCHAR(190) NOT NULL,
  destination VARCHAR(190) NOT NULL,
  mode VARCHAR(80) NOT NULL,
  status VARCHAR(80) NOT NULL,
  next_step VARCHAR(255) NOT NULL,
  internal_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_shipments_client_email (client_email),
  INDEX idx_portal_shipments_status (status)
);

CREATE TABLE IF NOT EXISTS portal_shipment_updates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id INT UNSIGNED NOT NULL,
  shipment_reference VARCHAR(40) NOT NULL,
  status VARCHAR(80) NOT NULL,
  next_step VARCHAR(255) NOT NULL,
  notes TEXT NULL,
  actor_email VARCHAR(190) NULL,
  actor_name VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_portal_shipment_updates_shipment_id (shipment_id),
  INDEX idx_portal_shipment_updates_reference (shipment_reference)
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
  tracking_reference VARCHAR(40) NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency VARCHAR(12) NOT NULL DEFAULT 'KES',
  status VARCHAR(80) NOT NULL,
  due_date DATE NOT NULL,
  bank_name VARCHAR(190) NULL,
  account_name VARCHAR(190) NULL,
  account_number VARCHAR(120) NULL,
  bank_branch VARCHAR(190) NULL,
  swift_code VARCHAR(80) NULL,
  mpesa_details TEXT NULL,
  mpesa_pay_link VARCHAR(255) NULL,
  paypal_details TEXT NULL,
  paypal_pay_link VARCHAR(255) NULL,
  payment_instructions TEXT NULL,
  payment_reference VARCHAR(190) NULL,
  payment_notes TEXT NULL,
  payment_submitted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_invoices_client_email (client_email),
  INDEX idx_portal_invoices_status (status)
);

CREATE TABLE IF NOT EXISTS portal_incoming_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_email VARCHAR(190) NOT NULL,
  client_name VARCHAR(190) NOT NULL,
  supplier_name VARCHAR(190) NOT NULL,
  supplier_tracking_number VARCHAR(120) NOT NULL,
  item_description TEXT NOT NULL,
  origin VARCHAR(190) NOT NULL,
  expected_arrival DATE NULL,
  status VARCHAR(80) NOT NULL DEFAULT 'Submitted',
  assigned_to VARCHAR(190) NULL,
  assigned_name VARCHAR(190) NULL,
  linked_shipment_id INT UNSIGNED NULL,
  linked_shipment_reference VARCHAR(40) NULL,
  converted_at DATETIME NULL,
  admin_notes TEXT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_incoming_requests_assigned_to (assigned_to),
  INDEX idx_portal_incoming_requests_client_email (client_email),
  INDEX idx_portal_incoming_requests_status (status)
);

CREATE TABLE IF NOT EXISTS portal_quote_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_type VARCHAR(40) NOT NULL,
  client_email VARCHAR(190) NULL,
  client_name VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NULL,
  shipment_type VARCHAR(80) NULL,
  origin VARCHAR(190) NULL,
  destination VARCHAR(190) NULL,
  mode VARCHAR(80) NULL,
  weight VARCHAR(80) NULL,
  product_category VARCHAR(190) NULL,
  quantity VARCHAR(120) NULL,
  market VARCHAR(190) NULL,
  budget VARCHAR(190) NULL,
  product_details TEXT NULL,
  notes TEXT NULL,
  status VARCHAR(80) NOT NULL DEFAULT 'Submitted',
  assigned_to VARCHAR(190) NULL,
  assigned_name VARCHAR(190) NULL,
  admin_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_portal_quote_requests_assigned_to (assigned_to),
  INDEX idx_portal_quote_requests_request_type (request_type),
  INDEX idx_portal_quote_requests_status (status),
  INDEX idx_portal_quote_requests_email (client_email)
);

ALTER TABLE portal_shipments ADD COLUMN IF NOT EXISTS assigned_to VARCHAR(190) NULL AFTER client_name;
ALTER TABLE portal_shipments ADD COLUMN IF NOT EXISTS assigned_name VARCHAR(190) NULL AFTER assigned_to;
ALTER TABLE portal_shipments ADD COLUMN IF NOT EXISTS internal_notes TEXT NULL AFTER next_step;
ALTER TABLE portal_shipments ADD COLUMN IF NOT EXISTS incoming_request_id INT UNSIGNED NULL AFTER client_name;
ALTER TABLE portal_incoming_requests ADD COLUMN IF NOT EXISTS assigned_to VARCHAR(190) NULL AFTER status;
ALTER TABLE portal_incoming_requests ADD COLUMN IF NOT EXISTS assigned_name VARCHAR(190) NULL AFTER assigned_to;
ALTER TABLE portal_incoming_requests ADD COLUMN IF NOT EXISTS linked_shipment_id INT UNSIGNED NULL AFTER assigned_name;
ALTER TABLE portal_incoming_requests ADD COLUMN IF NOT EXISTS linked_shipment_reference VARCHAR(40) NULL AFTER linked_shipment_id;
ALTER TABLE portal_incoming_requests ADD COLUMN IF NOT EXISTS converted_at DATETIME NULL AFTER linked_shipment_reference;
ALTER TABLE portal_incoming_requests ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL AFTER converted_at;
ALTER TABLE portal_quote_requests ADD COLUMN IF NOT EXISTS assigned_to VARCHAR(190) NULL AFTER status;
ALTER TABLE portal_quote_requests ADD COLUMN IF NOT EXISTS assigned_name VARCHAR(190) NULL AFTER assigned_to;
ALTER TABLE portal_quote_requests ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL AFTER assigned_name;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS tracking_reference VARCHAR(40) NULL AFTER client_name;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS bank_name VARCHAR(190) NULL AFTER due_date;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS account_name VARCHAR(190) NULL AFTER bank_name;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS account_number VARCHAR(120) NULL AFTER account_name;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS bank_branch VARCHAR(190) NULL AFTER account_number;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS swift_code VARCHAR(80) NULL AFTER bank_branch;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS mpesa_details TEXT NULL AFTER swift_code;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS mpesa_pay_link VARCHAR(255) NULL AFTER mpesa_details;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS paypal_details TEXT NULL AFTER mpesa_pay_link;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS paypal_pay_link VARCHAR(255) NULL AFTER paypal_details;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS payment_instructions TEXT NULL AFTER paypal_pay_link;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(190) NULL AFTER payment_instructions;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS payment_notes TEXT NULL AFTER payment_reference;
ALTER TABLE portal_invoices ADD COLUMN IF NOT EXISTS payment_submitted_at DATETIME NULL AFTER payment_notes;
