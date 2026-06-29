-- DBG Platform Workflow Tables
-- Status: Draft v1.0

CREATE TABLE events (
  id BIGINT PRIMARY KEY,
  event_type VARCHAR(128) NOT NULL,
  organisation_id BIGINT,
  project_id BIGINT,
  actor_id BIGINT,
  payload TEXT,
  occurred_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (actor_id) REFERENCES users(id)
);

CREATE TABLE workflows (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  trigger_event VARCHAR(128) NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
);

CREATE TABLE workflow_runs (
  id BIGINT PRIMARY KEY,
  workflow_id BIGINT NOT NULL,
  event_id BIGINT NOT NULL,
  status VARCHAR(64) NOT NULL DEFAULT 'running',
  started_at TIMESTAMP NOT NULL,
  completed_at TIMESTAMP,
  FOREIGN KEY (workflow_id) REFERENCES workflows(id),
  FOREIGN KEY (event_id) REFERENCES events(id)
);

CREATE TABLE validation_requests (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT NOT NULL,
  project_id BIGINT,
  asset_id BIGINT,
  requested_by BIGINT,
  assigned_to BIGINT,
  status VARCHAR(64) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL,
  resolved_at TIMESTAMP,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (project_id) REFERENCES projects(id),
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (requested_by) REFERENCES users(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE notifications (
  id BIGINT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  event_id BIGINT,
  title VARCHAR(255) NOT NULL,
  body TEXT,
  status VARCHAR(64) NOT NULL DEFAULT 'unread',
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (event_id) REFERENCES events(id)
);

CREATE TABLE audit_logs (
  id BIGINT PRIMARY KEY,
  organisation_id BIGINT,
  actor_id BIGINT,
  action VARCHAR(128) NOT NULL,
  entity_type VARCHAR(128),
  entity_id BIGINT,
  payload TEXT,
  created_at TIMESTAMP NOT NULL,
  FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  FOREIGN KEY (actor_id) REFERENCES users(id)
);
