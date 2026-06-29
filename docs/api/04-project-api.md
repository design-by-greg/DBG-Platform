# 04 Project API

Status: Draft v1.0

## Endpoints

### GET /organisations/{organisation_id}/projects

List Projects for an Organisation.

### POST /organisations/{organisation_id}/projects

Create a Project.

Required fields:
- name
- workspace_id optional
- brand_id optional

### GET /projects/{project_id}

Get Project details.

### PATCH /projects/{project_id}

Update Project.

### GET /projects/{project_id}/timeline

Get Project timeline.

### GET /projects/{project_id}/assets

List Assets attached to a Project.

### GET /projects/{project_id}/orders

List Orders attached to a Project.

## Events

- project.created
- project.updated
- project.archived
