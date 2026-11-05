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
Organization workspace records are stored in the database in organizations.workspace_data.
Select an organization from its management page before editing records. Membership or
super-admin access is checked on every save. Version checks reject stale writes.
New organizations have no sample records; checklist definitions start unchecked.
Legacy browser-local records are not imported automatically. Certificate and profile
picture files are private. Developer controls at /admin allow user and organization
editing, organization verification, and MyKKP certificate review.
The QR points to /workerportal on the current host; localhost is only reachable
on the machine running the app. Scores remain illustrative prototype indicators.
Old profile, health, languages and oshwa URLs redirect to their Adapt360 equivalents.
The importer in scripts/import-adapt360.mjs regenerates reference page partials
and the reference controller; app integration and visual styling are separate.
Run `npm run build` after frontend changes, or use `npm run dev` for Vite.
When configuring Apache, use the project's `public` directory as the document root.

## Accounts and external services

Open `/login` or `/register`. Profiles are free. Verify your email before submitting
a MyKKP certificate or registering an organization. The verified email
`sogerft@gmail.com` receives the super-admin role at login or verification; no shared
admin password is created. `/admin` provides user creation, organization provisioning,
and manual certificate approval or rejection. Owners add members by their existing
profile email. Super admins can explicitly grant organization access without billing.

Configure these values in `.env`, then run `php artisan config:clear`:

- `APP_URL`: the public application origin.
- `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`: credentials for a Google OAuth web application.
- `GOOGLE_REDIRECT_URI`: the exact registered callback, locally
  `http://localhost:8000/auth/google/callback`.
- `STRIPE_SECRET`: Stripe secret API key (use test mode during setup).
- `STRIPE_PRICE_ID`: an existing recurring Price ID; its amount, currency, and interval
  are configured in Stripe and displayed by hosted Checkout before payment.
- `STRIPE_WEBHOOK_SECRET`: signing secret for `/stripe/webhook`. Subscribe to
  `checkout.session.completed`, `customer.subscription.created`,
  `customer.subscription.updated`, and `customer.subscription.deleted`.
- Configure the Stripe Customer Portal to allow billing updates and cancellation.
- Configure Laravel's mail transport for verification and password setup/reset links.
  The local default `MAIL_MAILER=log` writes these messages to `storage/logs/laravel.log`
  instead of delivering email. Use a real transport for deployment.

### Brevo SMTP

In Brevo, activate transactional email, authenticate your sending domain, and create
a sender. Under **Settings > SMTP & API > SMTP**, copy the SMTP login and generate
an SMTP key. Add the following to your local `.env`, replacing the placeholders:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME="your-brevo-smtp-login"
MAIL_PASSWORD="your-brevo-smtp-key"
MAIL_FROM_ADDRESS="your-verified-sender@example.com"
MAIL_FROM_NAME="Adapt360"
```

Use the SMTP key, not the Brevo API key or account password. Set `APP_URL` to the
address recipients use to reach the app, then run `php artisan config:clear`.
Click **Resend verification email** and check the inbox and spam folder. If sending
fails, check the application log and Brevo's transactional email logs. Keep the SMTP
key private and never commit `.env`.

The checkout return URL does not activate access. Signed webhooks retrieve current
subscription status from Stripe. Only active subscriptions or explicit admin provisions
permit adding organization members. Google and Stripe return helpful configuration
errors until their credentials are supplied. Automated tests mock both providers;
real provider flows still need a configured sandbox end-to-end check.
