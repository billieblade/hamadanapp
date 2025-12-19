-- HamadanApp v4 - schema + users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('master','funcionario') NOT NULL DEFAULT 'funcionario',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo_os VARCHAR(40) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('aberta','fechada','cancelada') NOT NULL DEFAULT 'aberta',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_order_id INT NOT NULL,
  tipo_peca ENUM('retangular','redondo') NOT NULL DEFAULT 'retangular',
  largura_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  comprimento_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  diametro_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  qtd INT NOT NULL DEFAULT 1,
  etiqueta_codigo VARCHAR(60) NOT NULL UNIQUE,
  status_item ENUM('EM_TRANSITO','LAVANDERIA','REPAROS','SECAGEM','ESPERANDO_ENTREGA','FINALIZADO') NOT NULL DEFAULT 'EM_TRANSITO',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_item_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_item_id INT NOT NULL,
  service_id INT NOT NULL,
  unidade ENUM('m2','ml','peca') NOT NULL,
  qtd DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (work_item_id) REFERENCES work_order_items(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes (MySQL 8+ IF NOT EXISTS; em 5.7 ignore erros se já existirem)
CREATE INDEX IF NOT EXISTS idx_services_cat ON services(categoria, ativo, nome);
CREATE INDEX IF NOT EXISTS idx_wo_customer ON work_orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_wo_status ON work_orders(status);
CREATE INDEX IF NOT EXISTS idx_items_status ON work_order_items(status_item);
CREATE INDEX IF NOT EXISTS idx_item_services ON work_item_services(work_item_id, service_id);

-- Users
INSERT IGNORE INTO users (id,name,email,password_hash,role,ativo)
VALUES (1,'Master','admin@admin.com','$2y$10$JktlcW9sFQ6iZt1y7Jv29uZ3bQn4F2QvZg2mN3rJrXv9XkqQwN7ZK','master',1);
INSERT IGNORE INTO users (id,name,email,password_hash,role,ativo)
VALUES (2,'Funcionário','func@hamadan.com','$2y$10$CwTycUXWue0Thq9StjUM0uJ8dYh4G6Q2iYkE2V6ZcZlXz7zQ0f/3K','funcionario',1);
