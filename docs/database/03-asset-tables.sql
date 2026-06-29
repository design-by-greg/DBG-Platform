-- DBG Platform Asset Tables
-- Status: Draft v1.0

CREATE TABLE assets (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  project_id BIGINT,
  type VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'draft',
  current_version_id BIGINT,
  created_by BIGINT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE asset_versions (
  id BIGINT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  version_number INTEGER NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'draft',
  created_by BIGINT,
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE resources (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  asset_id BIGINT,
  asset_version_id BIGINT,
  storage_provider VARCHAR(64) NOT NULL,
  path TEXT NOT NULL,
  mime_type VARCHAR(128),
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (asset_version_id) REFERENCES asset_versions(id)
);

CREATE TABLE asset_relations (
  id BIGINT PRIMARY KEY,
  source_asset_id BIGINT NOT NULL,
  target_asset_id BIGINT NOT NULL,
  relation_type VARCHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (source_asset_id) REFERENCES assets(id),
  FOREIGN KEY (target_asset_id) REFERENCES assets(id)
);

CREATE TABLE product_library_items (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  asset_id BIGINT NOT NULL,
  source_product_id VARCHAR(128),
  reorder_enabled BOOLEAN NOT NULL DEFAULT TRUE,
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (asset_id) REFERENCES assets(id)
);
