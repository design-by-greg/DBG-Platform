# 08 Production API

Status: Draft v1.0

## Endpoints

### GET /production/jobs

List Production Jobs.

### POST /production/jobs

Create a Production Job.

Required fields:
- order_id
- supplier_id optional

### GET /production/jobs/{job_id}

Get Production Job details.

### PATCH /production/jobs/{job_id}

Update Production Job status.

### POST /production/jobs/{job_id}/quality-checks

Create Quality Check.

### POST /production/jobs/{job_id}/deliveries

Create Delivery.

### GET /suppliers

List suppliers.

### GET /suppliers/{supplier_id}

Get supplier details.

## Events

- production.job_created
- production.started
- production.quality_checked
- production.completed
- production.delivery_created
- production.delivered
