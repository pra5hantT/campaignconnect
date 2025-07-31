-- Database schema for CampaignConnect

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL
);

INSERT INTO roles (id, name) VALUES
  (1, 'Superadmin'),
  (2, 'Admin'),
  (3, 'User')
ON DUPLICATE KEY UPDATE name=VALUES(name);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100),
  name VARCHAR(100),
  role_id INT NOT NULL DEFAULT 3,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS external_databases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  db_type VARCHAR(20) NOT NULL,
  host VARCHAR(100) NOT NULL,
  port VARCHAR(10),
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  dbname VARCHAR(100) NOT NULL,
  status VARCHAR(20) DEFAULT 'inactive',
  last_sync DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS smtp_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_name VARCHAR(100) NOT NULL,
  from_name VARCHAR(100),
  from_email VARCHAR(100) NOT NULL,
  smtp_host VARCHAR(100) NOT NULL,
  smtp_port INT NOT NULL DEFAULT 25,
  smtp_username VARCHAR(100) NOT NULL,
  smtp_password VARCHAR(255) NOT NULL,
  daily_limit INT DEFAULT 0,
  usage_today INT DEFAULT 0,
  status VARCHAR(20) DEFAULT 'inactive',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contact_type VARCHAR(30) DEFAULT 'subscriber',
  status VARCHAR(20) DEFAULT 'active',
  last_activity DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_lists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  list_name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_list_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  list_id INT NOT NULL,
  contact_id INT NOT NULL,
  FOREIGN KEY (list_id) REFERENCES contact_lists(id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_name VARCHAR(100) NOT NULL,
  html_content MEDIUMTEXT NOT NULL,
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_name VARCHAR(100) NOT NULL,
  subject_line VARCHAR(150) NOT NULL,
  sender_name VARCHAR(100),
  sender_email VARCHAR(100) NOT NULL,
  campaign_type VARCHAR(50) DEFAULT 'newsletter',
  template_id INT NULL,
  content MEDIUMTEXT,
  status VARCHAR(20) DEFAULT 'draft',
  scheduled_time DATETIME NULL,
  sent_at DATETIME NULL,
  smtp_account_id INT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE SET NULL,
  FOREIGN KEY (smtp_account_id) REFERENCES smtp_accounts(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS campaign_recipients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  contact_id INT NOT NULL,
  sent_at DATETIME NULL,
  open_count INT DEFAULT 0,
  click_count INT DEFAULT 0,
  bounce TINYINT DEFAULT 0,
  status VARCHAR(20) DEFAULT 'scheduled',
  FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
  FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create a default superadmin user with username 'admin' and password 'admin123' hashed using SHA256
INSERT INTO users (username, password, email, name, role_id)
VALUES ('admin', LOWER(SHA2('admin123', 256)), 'admin@example.com', 'Administrator', 1)
ON DUPLICATE KEY UPDATE username=VALUES(username);