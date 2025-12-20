-- Schema
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('master','funcionario') NOT NULL DEFAULT 'funcionario',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(180) NOT NULL,
  tipo ENUM('final','corporativo') NOT NULL DEFAULT 'final',
  cpf_cnpj VARCHAR(32),
  email VARCHAR(180),
  telefone VARCHAR(40),
  endereco TEXT,
  observacoes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria ENUM('CORTINAS','PERSIANAS','CARPETE','ESTOFADOS','TAPETES') NOT NULL,
  nome VARCHAR(220) NOT NULL,
  unidade ENUM('m2','ml','peca') NOT NULL,
  preco_final DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  preco_corporativo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  observacao VARCHAR(255),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

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
);

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
);

CREATE TABLE IF NOT EXISTS quote_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NOT NULL,
  service_id INT NULL,
  tipo_tapete ENUM('retangular','redondo') DEFAULT 'retangular',
  largura_cm DECIMAL(10,2) NULL,
  comprimento_cm DECIMAL(10,2) NULL,
  diametro_cm DECIMAL(10,2) NULL,
  qtd INT DEFAULT 1,
  lacre_numero VARCHAR(60) NULL,
  preco_unitario DECIMAL(10,2) NULL,
  regra_aplicada_json JSON NULL,
  subtotal DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (quote_id) REFERENCES quotes(id)
);

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
);

CREATE TABLE IF NOT EXISTS work_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NULL,
  codigo_os VARCHAR(40) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('aberta','fechada','cancelada') NOT NULL DEFAULT 'aberta',
  status_pagamento ENUM('pendente','pago','inadimplente') NOT NULL DEFAULT 'pendente',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quote_id) REFERENCES quotes(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_order_id INT NOT NULL,
  valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  forma_pagto ENUM('dinheiro','debito','credito','pix') NOT NULL,
  emitido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  observacao TEXT,
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

CREATE TABLE IF NOT EXISTS work_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_order_id INT NOT NULL,
  quote_item_id INT NULL,
  tipo_peca ENUM('retangular','redondo') NOT NULL DEFAULT 'retangular',
  largura_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  comprimento_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  diametro_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  qtd INT NOT NULL DEFAULT 1,
  etiqueta_codigo VARCHAR(60) NOT NULL UNIQUE,
  lacre_numero VARCHAR(60) NULL,
  status_item ENUM('EM_TRANSITO','LAVANDERIA','REPAROS','SECAGEM','ESPERANDO_ENTREGA','FINALIZADO') NOT NULL DEFAULT 'EM_TRANSITO',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (quote_item_id) REFERENCES quote_items(id)
);

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
);

CREATE TABLE IF NOT EXISTS work_order_receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_order_id INT NOT NULL,
  valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  forma_pagto VARCHAR(40) NULL,
  banco VARCHAR(120) NULL,
  emitido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacao TEXT NULL,
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  acao VARCHAR(64),
  alvo VARCHAR(64),
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes (MySQL 8+ IF NOT EXISTS; em 5.7 ignore erros se já existirem)
CREATE INDEX IF NOT EXISTS idx_services_cat ON services(categoria, ativo, nome);
CREATE INDEX IF NOT EXISTS idx_services_all_cat ON services_all(categoria, ativo, nome);
CREATE INDEX IF NOT EXISTS idx_wo_customer ON work_orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_wo_status ON work_orders(status);
CREATE INDEX IF NOT EXISTS idx_receipts_work_order ON work_order_receipts(work_order_id);
CREATE INDEX IF NOT EXISTS idx_items_status ON work_order_items(status_item);
CREATE INDEX IF NOT EXISTS idx_item_services ON work_item_services(work_item_id, service_id);

-- Users
INSERT IGNORE INTO users (name,email,password_hash,role) VALUES
('Master','admin@admin.com', '$2y$10$7C1g6nC9u8Y0hGdQ9l8G7u7a6sZpXb2R6b3QO3sQ2YzR9J2QFz3F2', 'master');
