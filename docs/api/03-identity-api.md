# 03 Identity API

Status: Draft v1.0

## Endpoints

### GET /organisations

List Organisations available to the authenticated User.

### POST /organisations

Create an Organisation.

Required fields:
- name
- type

### GET /organisations/{organisation_id}

Get Organisation details.

### PATCH /organisations/{organisation_id}

Update Organisation details.

### GET /organisations/{organisation_id}/members

List Organisation members.

### POST /organisations/{organisation_id}/invitations

Invite a User to join an Organisation.

Required fields:
- email
- role_id

### GET /roles

List available Roles.

### GET /permissions

List available Permissions.

## Events

- identity.organisation_created
- identity.member_invited
- identity.member_joined
- identity.role_assigned
