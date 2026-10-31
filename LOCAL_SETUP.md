# Local setup

This project uses Laravel 13 and PHP 8.5 with Laravel's MySQL driver.
The local XAMPP database server is MariaDB 10.4.

## Database

Start MySQL in XAMPP before running the application.
The local database is `selamat_pulang` on `127.0.0.1:3306`.
Local credentials in `.env` use XAMPP's `root` user with an empty password.
`.env.example` contains the same database defaults, without an application key.

## Run

```powershell
composer install
npm install
npm run build
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000.
For a fresh checkout, copy `.env.example` to `.env` and run
`php artisan key:generate` before migrating.

## Verify

```powershell
php artisan migrate:status
php artisan test
```

The project contains 23 Adapt360 workspace modules using Blade, npm-installed
Tailwind CSS and Lucide icons. DESIGN.md is the Auralis Neural Audio Engine design reference.
The supplied Adapt360 HTML and logo are preserved in docs/reference and public/images.
Navigation, change-impact reviews, registers, actions, calendar, assessments,
checklists, mentor lessons and worker portal interactions run locally.
Prototype records persist in browser localStorage under adapt360_workspace_v1.
They are not shared between devices or stored in MySQL; the database still
contains Laravel's default tables. No authentication or real uploads are implemented.
The QR points to /workerportal on the current host; localhost is only reachable
on the machine running the app. Scores remain illustrative prototype indicators.
Old profile, health, languages and oshwa URLs redirect to their Adapt360 equivalents.
The importer in scripts/import-adapt360.mjs regenerates reference page partials
and the reference controller; app integration and visual styling are separate.
Run `npm run build` after frontend changes, or use `npm run dev` for Vite.
When configuring Apache, use the project's `public` directory as the document root.
