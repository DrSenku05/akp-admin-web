# AKP Native Products deployment

## Important architecture note

Vercel hosts the React/Vite frontend. The local XAMPP MySQL database and the Laravel API are not production services and cannot be reached by a deployed Vercel site. Deploy the Laravel API and MySQL database separately, then point Vercel at the API URL.

Recommended production layout:

```text
Vercel (React/Vite admin) -> HTTPS Laravel API -> managed MySQL
```

Use a managed MySQL provider such as Railway, Render, PlanetScale-compatible MySQL, or another provider approved by your team. Deploy Laravel on a PHP-capable host such as Laravel Cloud, Laravel Vapor, Render, Railway, or a PHP VPS.

## 1. Prepare the Laravel API

From `api/`, copy `.env.example` to `.env` and set production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-api-domain.example.com
APP_KEY=base64:GENERATED_VALUE

DB_CONNECTION=mysql
DB_HOST=your-managed-mysql-host
DB_PORT=3306
DB_DATABASE=akp_native_products
DB_USERNAME=your-production-user
DB_PASSWORD=your-production-password
```

Generate the key on the API host with `php artisan key:generate --show`, then configure the provider to run:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
```

Do not upload `api/.env`, local database credentials, or the XAMPP socket configuration to GitHub.

## 2. Deploy the Laravel API

This repository now includes `api/Dockerfile` and `render.yaml` for a free Render web service. Render supports Docker-based web services, but free services sleep after 15 minutes without traffic. [Render Docker deployment](https://render.com/docs/deploy-php-laravel-docker)

1. Push the repository to GitHub.
2. In Render, choose **New → Blueprint** and select the repository.
3. Review the `akp-native-products-api` service from `render.yaml`.
4. Add the secret environment values requested by Render: `APP_KEY`, `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `MYSQL_ATTR_SSL_CA`.
5. Paste the contents of the Aiven CA certificate into `MYSQL_ATTR_SSL_CA`, or use the host's certificate-file mechanism if supported by your plan.
6. Deploy and wait for `/api/health` to return `{"status":"ok"}`.

## 3. Configure production CORS

Allow only the final Vercel origin in the Laravel CORS configuration, for example:

```text
https://your-project.vercel.app
```

Do not use `*` once authenticated production traffic is enabled.

## 4. Deploy the frontend to Vercel

1. Push this repository to GitHub.
2. In Vercel, choose **New Project** and import the repository.
3. Set the project root to the repository root.
4. Vercel will use `vercel.json`, `npm run build`, and `dist`.
5. Add this Vercel environment variable:

```text
VITE_API_URL=https://your-api-domain.example.com/api
```

6. Deploy and open the generated Vercel URL.

## 5. Verify production

Check these URLs before testing the UI:

```bash
curl https://your-api-domain.example.com/api/health
curl -I https://your-project.vercel.app
```

Then log in with a production account created by your team. Change the seeded demo passwords before sharing the deployed system.

## Local development

Frontend:

```bash
npm install
cp .env.example .env.local
npm run dev
```

API with XAMPP:

```bash
cd api
cp .env.example .env
php artisan serve --host 127.0.0.1 --port 8000
```

For local frontend access, set `VITE_API_URL=http://127.0.0.1:8000/api` in `.env.local`.
