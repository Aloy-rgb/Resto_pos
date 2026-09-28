# Resto POS

Laravel 12 point-of-sale application for restaurant order entry, kitchen status, payment recording, staff accounts, and menu/table administration. The database uses MySQL and Laravel migrations.

## Requirements

- PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, and `ctype`
- Composer 2
- MySQL 8.0+ (or a compatible MariaDB release)
- Node.js 20.19+ and npm, for compiling the frontend assets

## Local installation

1. Create a MySQL database and user, then grant that user access to the database.
2. Install dependencies and create a local environment file:

   ```sh
   composer install
   cp .env.example .env
   ```

   On Windows, use `Copy-Item .env.example .env` in PowerShell.
3. For local HTTP development set `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL`, and `SESSION_SECURE_COOKIE=false` in `.env`; set the `DB_*` values as well. Set a mail provider if password resets should send email.
4. Create the application key, migrate the schema, and load the sample menu:

   ```sh
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   npm ci
   npm run build
   ```

5. Create the first administrator without a built-in/default password:

   ```sh
   php artisan tinker
   ```

   Then enter:

   ```php
   App\Models\User::create([
       'name' => 'Restaurant Administrator',
       'username' => 'owner',
       'email' => 'owner@example.com',
       'email_verified_at' => now(),
       'role' => 'admin',
       'password' => Illuminate\Support\Facades\Hash::make('12345678'),
   ]);
   ```

6. Point your web server to the `public/` directory and serve the app over HTTPS. For local development, run `composer run dev`.

The seeder creates sample categories and menu items only. It does not create users or default credentials. Administrators create staff accounts from the Staff page.

## Production deployment

1. Deploy the release with `vendor/` installed (`composer install --no-dev --prefer-dist --optimize-autoloader`) and compiled frontend assets (`npm ci && npm run build`). Never upload `.env`, expose the repository root as the document root, or enable debug mode publicly.
2. Configure the production `.env`: set `APP_ENV=production`, `APP_DEBUG=false`, the real HTTPS `APP_URL`, a unique `APP_KEY`, MySQL credentials, SMTP credentials, and `SESSION_SECURE_COOKIE=true`. Do not regenerate the key on later releases.
3. Ensure `storage/` and `bootstrap/cache/` are writable by the PHP runtime. Configure the web server document root to `<release>/public` and enable HTTPS.
4. Run database migrations during the release:

   ```sh
   php artisan migrate --force
   ```

The original migrations create the restaurant tables and a forward migration upgrades them for the current app while backfilling existing totals and payment state. The runtime migration creates database-backed sessions, cache, password reset, and queue tables. Avoid `migrate:fresh` and production rollbacks.
5. Cache production configuration and routes after environment values are final:

   ```sh
   php artisan optimize
   ```

6. Run a queue worker if background jobs are dispatched, and schedule Laravel's scheduler once per minute if scheduled tasks are added. Restart workers after each deployment (`php artisan queue:restart`).
7. Back up MySQL and uploaded storage, and verify restore procedures before serving live orders.

## Main flows

- Cashiers create, edit, cancel, print checks for, and take payment on orders.
- Chefs move orders from pending to cooking to ready; cashiers mark ready orders served.
- Managers review sales totals and payment reconciliation.
- Administrators manage staff, menu categories, menu items, and dining tables.
- Paid orders retain their item names, quantities, unit prices, payment method, and invoice number in the database.
