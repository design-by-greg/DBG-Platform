# SPEC-005 Workflow

Status: Draft

## Mission

Workflow manages events, validations, notifications and state changes.

## Objects

Event
Workflow
Workflow Step
Notification
Validation Request
State Change

## Rules

Workflow coordinates modules.
Modules publish events.
Workflow reacts to events.
Important state changes are logged.

## Main events

Organisation created
Project created
Asset created
Asset validated
Price requested
Order created
BAT requested
BAT validated
Production started
Production completed
Delivery completed
Reorder requested

## Flow BAT validation

Asset is submitted.
Workflow creates validation request.
User validates or rejects.
Status is updated.
Event is published.

## Acceptance

Receive event.
Trigger workflow.
Create validation request.
Send notification.
Update status.
Log action.
