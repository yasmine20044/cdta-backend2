# CDTA Backend Setup & Running Guide

This guide provides step-by-step instructions to set up, configure, and run the Laravel backend for the CDTA application.

## Prerequisites
- **PHP 8.3+**
- **Composer**
- **MySQL 8.4+** (Using Laragon or similar local server environment)

---

## 1. Environment Setup
First, ensure you have your `.env` file correctly configured. If you don't have one, copy the `.env.example`:
```bash
cp .env.example .env
```

Make sure your `.env` has the correct database and email settings:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cdta_db
DB_USERNAME=root
DB_PASSWORD=

# Mail Configurations (Google OAuth)
MAIL_MAILER=gmail-oauth

EMAIL_USER=your_email@gmail.com
CLIENT_ID=your_google_client_id
CLIENT_SECRET=your_google_client_secret
REFRESH_TOKEN=your_google_refresh_token
```

---

## 2. Install Dependencies
Navigate into the backend directory and install the required PHP packages via Composer:

```bash
cd cdta-backend2
composer install
```

Generate the application key (if not already done):
```bash
php artisan key:generate
```

---

## 3. Database Migration & Seeding
In order to build the database tables and populate the necessary data (like the mega-menu structure), run the following commands:

Run the migrations to create all tables:
```bash
php artisan migrate
```

Seed the default database values (User roles, etc.):
```bash
php artisan db:seed
```

Seed the exact CDTA Navigation Menu structure:
```bash
php artisan db:seed --class=NavItemSeeder
```

---

## 4. Setup Authentication (Super Admin)
Because public registration is disabled for security, you need to create the initial Super Admin via the secure custom CLI command:

```bash
php artisan make:super-admin
```
*(Follow the interactive prompts to set the admin's name, email, and password)*

---

## 5. Clear Caches
Always clear the configuration and application cache after modifying `.env` parameters or pulling new changes, especially for the Gmail OAuth mailer to register successfully:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 6. Run the Server
If you are using Laragon, the backend might already be accessible via your local `.test` domain (e.g., `http://cdta-backend2.test`). 

Otherwise, you can use PHP's built-in artisan server:
```bash
php artisan serve
```
*(This will start the API at `http://127.0.0.1:8000`)*

---

## Frontend Setup (Bonus)
To run the React frontend alongside the backend:

```bash
cd ../FRONTEND
npm install
npm run dev
```