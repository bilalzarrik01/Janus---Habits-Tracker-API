# AWS Deploy (App Runner + RDS MySQL)

This project now includes a production Docker image (`Dockerfile`) ready for AWS App Runner.

## 1. Create an RDS MySQL database

- Engine: MySQL 8
- Public access: `No` (recommended)
- Put App Runner service in the same VPC/subnets (via VPC connector)
- Keep connection values:
  - `DB_HOST`
  - `DB_PORT` (usually `3306`)
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`

## 2. Build and push image to ECR

```bash
aws ecr create-repository --repository-name janus-habits-api

aws ecr get-login-password --region <REGION> \
  | docker login --username AWS --password-stdin <ACCOUNT_ID>.dkr.ecr.<REGION>.amazonaws.com

docker build -t janus-habits-api .
docker tag janus-habits-api:latest <ACCOUNT_ID>.dkr.ecr.<REGION>.amazonaws.com/janus-habits-api:latest
docker push <ACCOUNT_ID>.dkr.ecr.<REGION>.amazonaws.com/janus-habits-api:latest
```

## 3. Create App Runner service

- Source: `Amazon ECR`
- Port: `80`
- Health check path: `/up`
- CPU/RAM: start with `1 vCPU / 2 GB`
- Attach VPC connector if RDS is private

Set environment variables in App Runner:

```env
APP_NAME=Janus
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-app-runner-domain>
APP_KEY=base64:REPLACE_WITH_REAL_KEY

DB_CONNECTION=mysql
DB_HOST=<rds-endpoint>
DB_PORT=3306
DB_DATABASE=janus_habits
DB_USERNAME=<db-user>
DB_PASSWORD=<db-password>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Generate `APP_KEY` locally:

```bash
php artisan key:generate --show
```

## 4. Run migrations once

Option A (recommended): run one-off command from a temporary container/task:

```bash
php artisan migrate --force
```

Option B: set `RUN_MIGRATIONS=true` in App Runner env vars for one deployment, then remove it.

## 5. Validate

- Health check: `GET /up`
- API check: `POST /api/login` and `GET /api/habits` with token

## Notes

- Keep `APP_DEBUG=false` in production.
- Use AWS Secrets Manager for DB password.
- Enable HTTPS custom domain in App Runner if needed.
