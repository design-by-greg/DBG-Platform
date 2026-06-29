-- DBG Platform Commerce Tables
-- Status: Draft v1.0

CREATE TABLE catalogue_items (
  id BIGINT PRIMARY KEY,
  provider VARCHAR(64),
  provider_product_id VARCHAR(128),
  name VARCHAR(255) NOT NULL,
  category VARCHAR(255),
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
);

CREATE TABLE configurations (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  project_id BIGINT,
  catalogue_item_id BIGINT,
  payload TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (catalogue_item_id) REFERENCES catalogue_items(id)
);

CREATE TABLE quotes (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  project_id BIGINT,
  configuration_id BIGINT,
  total_ht DECIMAL(12,2),
  total_ttc DECIMAL(12,2),
  status VARCHAR(64) NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (configuration_id) REFERENCES configurations(id)
);

CREATE TABLE orders (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  project_id BIGINT,
  quote_id BIGINT,
  status VARCHAR(64) NOT NULL DEFAULT 'draft',
  total_ht DECIMAL(12,2),
  total_ttc DECIMAL(12,2),
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (quote_id) REFERENCES quotes(id)
);

CREATE TABLE order_items (
  id BIGINT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  asset_id BIGINT,
  catalogue_item_id BIGINT,
  quantity INTEGER NOT NULL,
  unit_price_ht DECIMAL(12,2),
  total_ht DECIMAL(12,2),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (catalogue_item_id) REFERENCES catalogue_items(id)
);
