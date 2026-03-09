# AWS Deploy (Easy, No Docker) - Elastic Beanstalk + RDS

This is the simplest AWS path for this Laravel API without Docker.

## 1. Prerequisites

- Active AWS account
- AWS CLI configured (`aws configure`)
- Existing MySQL database on RDS

Quick check:

```bash
aws sts get-caller-identity
```

## 2. Build source bundle

From project root:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\build-eb-bundle.ps1
```

This creates:

- `dist/janus-eb.zip`

## 3. Create Elastic Beanstalk application (Console)

- Open AWS Console -> Elastic Beanstalk -> Create application
- Platform: `PHP`
- Upload code: `dist/janus-eb.zip`
- Environment type: `Web server`

The project includes `.ebextensions/php-settings.config` to force Laravel public root:

- `document_root=/public`

## 4. Set environment variables (Elastic Beanstalk)

In environment settings -> Software -> Environment properties:

```env
APP_NAME=Janus
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-eb-domain>
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

Generate app key locally:

```bash
php artisan key:generate --show
```

## 5. Run migrations

Use EB console command or SSH to environment and run:

```bash
php artisan migrate --force
```

## 6. Verify

- `GET /up`
- `POST /api/login`
- `GET /api/habits` (with token)
