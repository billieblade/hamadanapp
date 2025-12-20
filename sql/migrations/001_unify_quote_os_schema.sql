-- Migration: unify quote-based OS schema
-- Target: MySQL 8+ (uses ADD COLUMN IF NOT EXISTS)

-- Customers: add observacoes column if missing
ALTER TABLE customers
  ADD COLUMN IF NOT EXISTS observacoes TEXT;

-- Services catalog used by quotes/OS
CREATE TABLE IF NOT EXISTS services_all (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria ENUM('CORTINAS','PERSIANAS','CARPETE','ESTOFADOS','TAPETES') NOT NULL,
  nome VARCHAR(160) NOT NULL,
  unidade ENUM('m2','ml','peca') NOT NULL DEFAULT 'm2',
  preco_final DECIMAL(10,2) NOT NULL DEFAULT 0,
  preco_corporativo DECIMAL(10,2) NOT NULL DEFAULT 0,
  observacao VARCHAR(255),
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_services_all_cat ON services_all(categoria, ativo, nome);

-- Quotes and quote items
CREATE TABLE IF NOT EXISTS quotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  user_id INT NOT NULL,
  price_list_id INT NULL,
  forma_pagto ENUM('dinheiro','debito','credito','pix') NULL,
  status ENUM('rascunho','aguardando','aprovado','cancelado') DEFAULT 'rascunho',
  subtotal DECIMAL(10,2) DEFAULT 0,
  desconto DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NOT NULL,
  service_id INT NULL,
  tipo_tapete ENUM('retangular','redondo') DEFAULT 'retangular',
  largura_cm DECIMAL(10,2) NULL,
  comprimento_cm DECIMAL(10,2) NULL,
  diametro_cm DECIMAL(10,2) NULL,
  qtd INT DEFAULT 1,
  preco_unitario DECIMAL(10,2) NULL,
  regra_aplicada_json JSON NULL,
  subtotal DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (quote_id) REFERENCES quotes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_item_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_item_id INT NOT NULL,
  service_all_id INT NOT NULL,
  qtd DECIMAL(10,2) NOT NULL DEFAULT 1,
  preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quote_item_id) REFERENCES quote_items(id) ON DELETE CASCADE,
  FOREIGN KEY (service_all_id) REFERENCES services_all(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Work orders: ensure columns required by quote flow
ALTER TABLE work_orders
  ADD COLUMN IF NOT EXISTS quote_id INT NULL,
  ADD COLUMN IF NOT EXISTS customer_id INT NULL,
  ADD COLUMN IF NOT EXISTS user_id INT NULL,
  ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS total DECIMAL(10,2) NOT NULL DEFAULT 0.00;

SET @fk_work_orders_quote := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'work_orders'
    AND CONSTRAINT_NAME = 'fk_work_orders_quote'
  LIMIT 1
);
SET @sql := IF(@fk_work_orders_quote IS NULL,
  'ALTER TABLE work_orders ADD CONSTRAINT fk_work_orders_quote FOREIGN KEY (quote_id) REFERENCES quotes(id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_work_orders_customer := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'work_orders'
    AND CONSTRAINT_NAME = 'fk_work_orders_customer'
  LIMIT 1
);
SET @sql := IF(@fk_work_orders_customer IS NULL,
  'ALTER TABLE work_orders ADD CONSTRAINT fk_work_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_work_orders_user := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'work_orders'
    AND CONSTRAINT_NAME = 'fk_work_orders_user'
  LIMIT 1
);
SET @sql := IF(@fk_work_orders_user IS NULL,
  'ALTER TABLE work_orders ADD CONSTRAINT fk_work_orders_user FOREIGN KEY (user_id) REFERENCES users(id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE INDEX IF NOT EXISTS idx_wo_customer ON work_orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_wo_status ON work_orders(status);

-- Work order items: ensure quote reference + subtotal
ALTER TABLE work_order_items
  ADD COLUMN IF NOT EXISTS quote_item_id INT NULL,
  ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00;

SET @fk_work_order_items_quote_item := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'work_order_items'
    AND CONSTRAINT_NAME = 'fk_work_order_items_quote_item'
  LIMIT 1
);
SET @sql := IF(@fk_work_order_items_quote_item IS NULL,
  'ALTER TABLE work_order_items ADD CONSTRAINT fk_work_order_items_quote_item FOREIGN KEY (quote_item_id) REFERENCES quote_items(id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Work item services: enforce FK to services_all (not services)
CREATE TABLE IF NOT EXISTS work_item_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_item_id INT NOT NULL,
  service_id INT NOT NULL,
  unidade ENUM('m2','ml','peca') NOT NULL,
  qtd DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (work_item_id) REFERENCES work_order_items(id),
  FOREIGN KEY (service_id) REFERENCES services_all(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX IF NOT EXISTS idx_item_services ON work_item_services(work_item_id, service_id);

-- Drop any FK from work_item_services.service_id to services (if present)
SET @fk_name := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'work_item_services'
    AND COLUMN_NAME = 'service_id'
    AND REFERENCED_TABLE_NAME = 'services'
  LIMIT 1
);
SET @sql := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE work_item_services DROP FOREIGN KEY ', @fk_name));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure FK to services_all exists
SET @fk_work_item_services_service_all := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'work_item_services'
    AND CONSTRAINT_NAME = 'fk_work_item_services_service_all'
  LIMIT 1
);
SET @sql := IF(@fk_work_item_services_service_all IS NULL,
  'ALTER TABLE work_item_services ADD CONSTRAINT fk_work_item_services_service_all FOREIGN KEY (service_id) REFERENCES services_all(id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
