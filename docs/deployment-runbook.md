# Deployment Runbook

Operational notes for AWS API deployments.


### 2026-03-11T09:00:00+00:00
- Established a central runbook to keep deployment decisions and procedures consistent across environments.

### 2026-03-11T12:30:00+00:00
- Added a pre-release checklist to validate APP_ and DB_ variables before any deployment.

### 2026-03-11T16:10:00+00:00
- Captured inbound/outbound security group checks required for API-to-database connectivity.

### 2026-03-12T09:25:00+00:00
- Documented migration execution sequence and verification points for production-like environments.

### 2026-03-12T12:15:00+00:00
- Defined minimum endpoint checks to validate auth and core API behavior after release.

### 2026-03-12T17:05:00+00:00
- Added rollback triggers based on deployment health, endpoint failures, and critical log signals.

### 2026-03-13T09:10:00+00:00
- Included a short triage flow to isolate runtime, configuration, and data-layer failures.

### 2026-03-13T12:40:00+00:00
- Added repeatable commands to gather Laravel, Nginx, and PHP-FPM errors for investigation.
