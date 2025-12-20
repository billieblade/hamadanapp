-- Add payment status to work_orders and create work_order_receipts
ALTER TABLE work_orders
  ADD COLUMN status_pagamento ENUM('pendente','pago','inadimplente') NOT NULL DEFAULT 'pendente' AFTER status;

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

CREATE INDEX IF NOT EXISTS idx_receipts_work_order ON work_order_receipts(work_order_id);
