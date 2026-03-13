# Janus - Habits Tracker API

REST API for habit tracking built with Laravel 12 and Sanctum personal access tokens.

## Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- MySQL 8 (default in `.env.example`) or PostgreSQL 15

## Unified JSON Responses

All endpoints return strict JSON:

- Success: `{"success": true, "data": {...}, "message": "Operation reussie"}`
- Error: `{"success": false, "errors": {...}, "message": "Erreur de validation"}`

## API Endpoints

### Auth

- `POST /api/register`
- `POST /api/login`
- `POST /api/logout` (auth)
- `GET /api/me` (auth)

### Habits

- `GET /api/habits` (auth)
- `POST /api/habits` (auth)
- `GET /api/habits/{id}` (auth)
- `PUT /api/habits/{id}` (auth)
- `DELETE /api/habits/{id}` (auth)

### Logs & Stats

- `POST /api/habits/{id}/logs` (auth)
- `GET /api/habits/{id}/logs` (auth)
- `DELETE /api/habits/{id}/logs/{logId}` (auth)
- `GET /api/habits/{id}/stats` (auth)
- `GET /api/stats/overview` (auth)

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## MySQL Setup (Recommended)

Project is already configured for MySQL in `.env.example`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=janus_habits
DB_USERNAME=root
DB_PASSWORD=
```

Create database manually, then run migrations:

```sql
CREATE DATABASE janus_habits CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
```

Or import the prepared SQL schema directly:

```bash
mysql -u root -p < database/mysql/janus_habits.sql
```

## Tests

```bash
php artisan test
```

## AWS Deployment

For production deployment on AWS (App Runner + RDS), see:

- `docs/aws-apprunner-deploy.md`

## Postman

Collection and environment files are available in `postman/`:

- `postman/Janus-Habits-Tracker.postman_collection.json`
- `postman/Janus-Habits-Tracker.postman_environment.json`

Quick run order:

1. Start API: `php artisan serve`
2. Run `Auth/Register` or `Auth/Login` (token is auto-saved)
3. Run `Habits/Create Habit` (habit_id auto-saved)
4. Run Logs/Stats requests
