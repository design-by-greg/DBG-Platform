# 07 Workflow API

Status: Draft v1.0

## Endpoints

### GET /events

List events with filters.

### POST /events

Create or ingest an event.

### GET /validation-requests

List validation requests.

### POST /validation-requests

Create a validation request.

### POST /validation-requests/{request_id}/accept

Accept a validation request.

### POST /validation-requests/{request_id}/reject

Reject a validation request.

### GET /notifications

List user notifications.

### PATCH /notifications/{notification_id}

Update notification status.

## Events

- workflow.validation_requested
- workflow.validation_accepted
- workflow.validation_rejected
- workflow.notification_created
- workflow.state_changed
