-- Schema
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120), email VARCHAR(160) UNIQUE,
  password_hash VARCHAR(255),
  role ENUM('master','operador') DEFAULT 'operador',
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('final','corporativo') NOT NULL,
  nome VARCHAR(160) NOT NULL,
  cpf_cnpj VARCHAR(32),
  endereco VARCHAR(255),
  telefone VARCHAR(64),
  email VARCHAR(160),
  obs TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS price_lists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120), publico ENUM('final','corporativo'),
  ativo TINYINT(1) DEFAULT 1,
  vigencia_ini DATE, vigencia_fim DATE,
  criado_por INT NULL, atualizado_por INT NULL
);

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  price_list_id INT, nome VARCHAR(160),
  unidade ENUM('m2','ml','peca') NOT NULL,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0,
  observacao VARCHAR(255),
  ativo TINYINT(1) DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (price_list_id) REFERENCES price_lists(id)
);

CREATE TABLE IF NOT EXISTS quotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT, user_id INT, price_list_id INT,
  forma_pagto ENUM('dinheiro','debito','credito','pix') NULL,
  status ENUM('rascunho','aguardando','aprovado','cancelado') DEFAULT 'rascunho',
  subtotal DECIMAL(10,2) DEFAULT 0, desconto DECIMAL(10,2) DEFAULT 0, total DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS quote_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT, service_id INT,
  tipo_tapete ENUM('retangular','redondo') DEFAULT 'retangular',
  largura_cm DECIMAL(10,2) NULL, comprimento_cm DECIMAL(10,2) NULL,
  diametro_cm DECIMAL(10,2) NULL, qtd INT DEFAULT 1,
  preco_unitario DECIMAL(10,2) NULL,
  regra_aplicada_json JSON NULL,
  subtotal DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (quote_id) REFERENCES quotes(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE IF NOT EXISTS work_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT, codigo_os VARCHAR(32) UNIQUE,
  status ENUM('aberta','coleta','lavagem','acabamento','pronta','entregue') DEFAULT 'aberta',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quote_id) REFERENCES quotes(id)
);

CREATE TABLE IF NOT EXISTS work_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_order_id INT, quote_item_id INT,
  etiqueta_codigo VARCHAR(64) UNIQUE, status_item VARCHAR(32) DEFAULT 'aberto',
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (quote_item_id) REFERENCES quote_items(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT, acao VARCHAR(64), alvo VARCHAR(64),
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Master default: admin@admin.com / admin123
INSERT IGNORE INTO users (name,email,password_hash,role) VALUES
('Master','admin@admin.com', '$2y$10$7C1g6nC9u8Y0hGdQ9l8G7u7a6sZpXb2R6b3QO3sQ2YzR9J2QFz3F2', 'master');

-- Create two price lists (empty services for now; import via CSV)
INSERT IGNORE INTO price_lists (nome, publico, ativo, vigencia_ini, vigencia_fim) VALUES
('Clientes Finais - Agosto/2025', 'final', 1, CURDATE(), NULL),
('Corporativos - Agosto/2025', 'corporativo', 1, CURDATE(), NULL);
