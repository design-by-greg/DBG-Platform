-- DBG Platform Core Tables
-- Status: Draft v1.0

CREATE TABLE organisations (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(64) NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
);

CREATE TABLE users (
  id BIGINT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  display_name VARCHAR(255),
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
);

CREATE TABLE memberships (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE brands (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id)
);

CREATE TABLE workspaces (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  brand_id BIGINT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(64),
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (brand_id) REFERENCES brands(id)
);

CREATE TABLE projects (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  brand_id BIGINT,
  workspace_id BIGINT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  status VARCHAR(64) NOT NULL DEFAULT 'draft',
  created_by BIGINT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (brand_id) REFERENCES brands(id),
  FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
