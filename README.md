# VM3000

Single-school MVP for planning Projekt-/Vorhabenswochen.

## Setup

1. Create database `vm3000` in phpMyAdmin (utf8mb4).
2. Import `sql/schema.sql`.
3. Copy `.env.php.example` to `.env.php` and configure DB credentials.
4. Point your web server document root to `/public` (e.g., XAMPP Apache).
5. Open landing page: `http://localhost/`.
6. Teacher page example: `http://localhost/TSS`.

## Seeded credentials

- Admin: `admin@example.com` / `admin1234`
- Teacher initial password for sample week: `teach1234`

## Notes

- Billing flags are defined in `config.php` and currently disabled.
- CSV exports are available in the Admin dashboard.
