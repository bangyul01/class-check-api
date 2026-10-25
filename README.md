# School Attendance Management API

PHP + PostgreSQL REST API, deployed on Render with Docker.

## Endpoints

| Resource | Routes | Access |
|---|---|---|
| Auth | `POST /api/auth/register`, `/login`, `/logout` | register/login public |
| Users | `GET/PUT/DELETE /api/users[/{id}]` | admin |
| Subjects | `GET/POST/PUT/DELETE /api/subjects[/{id}]` | admin |
| Rooms | `GET/POST/PUT/DELETE /api/rooms[/{id}]` | admin |
| Classes | `GET/POST/PUT/DELETE /api/classes[/{id}]` | read: any user, write: admin |
| Attendances | `GET /api/attendances[/{id}]`, `/class/{id}`, `/user/{id}`, `POST` (check-in), `PUT`, `DELETE` | role-based |

Send `Authorization: Bearer <token>` on every request except register/login.

## Setup

1. Create a PostgreSQL database on Render and run `schema.sql` in it.
2. Push this repo to GitHub.
3. On Render: New > Web Service > pick the repo > Runtime **Docker**.
4. Add environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   (optional: `DB_PORT`, and `DB_SSLMODE=require` if you use the external DB host).
5. Make your first admin: register a user, then in the DB console run:
   `UPDATE users SET role = 'admin' WHERE email = 'you@example.com';`

## Run locally (needs PHP 8+ with pdo_pgsql and a Postgres DB)

```bash
export DB_HOST=localhost DB_NAME=attendance DB_USER=postgres DB_PASS=secret
php -S localhost:8000 index.php
```

## Postman

- Collection variable `base_url` = your Render URL.
- In the Login request's Tests tab: `pm.collectionVariables.set("token", pm.response.json().token);`
- Collection Authorization: Bearer Token `{{token}}`.
