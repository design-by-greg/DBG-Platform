-- DBG Platform Production Tables
-- Status: Draft v1.0

CREATE TABLE suppliers (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(64),
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
);

CREATE TABLE supplier_connectors (
  id BIGINT PRIMARY KEY,
  supplier_id BIGINT NOT NULL,
  connector_type VARCHAR(64) NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE production_jobs (
  id BIGINT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  supplier_id BIGINT,
  status VARCHAR(64) NOT NULL DEFAULT 'pending',
  due_date DATE,
  tracking_ref VARCHAR(255),
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE quality_checks (
  id BIGINT PRIMARY KEY,
  production_job_id BIGINT NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'pending',
  checked_by BIGINT,
  checked_at TIMESTAMP,
  notes TEXT,
  FOREIGN KEY (production_job_id) REFERENCES production_jobs(id),
  FOREIGN KEY (checked_by) REFERENCES users(id)
);

CREATE TABLE deliveries (
  id BIGINT PRIMARY KEY,
  production_job_id BIGINT NOT NULL,
  carrier VARCHAR(128),
  tracking_ref VARCHAR(255),
  status VARCHAR(64) NOT NULL DEFAULT 'pending',
  shipped_at TIMESTAMP,
  delivered_at TIMESTAMP,
  FOREIGN KEY (production_job_id) REFERENCES production_jobs(id)
);
