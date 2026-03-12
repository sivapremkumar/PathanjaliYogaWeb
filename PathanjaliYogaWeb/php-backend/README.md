<?php
// README.md
# YogaTrust PHP Backend

This is a PHP backend for the YogaTrust application, providing all features available in the .NET and Node.js versions.

## Features
- Admin authentication (JWT)
- Trustee management (CRUD)
- Donation management (with payment verification)
- Inquiry/contact form handling
- News & events management
- Admin dashboard stats

## Tech Stack
- PHP 8+
- Slim Framework 4
- Eloquent ORM (Illuminate)
- JWT Auth (firebase/php-jwt)

## Setup
1. Run `composer install` in this directory.
2. Copy `.env.example` to `.env` and update DB credentials.
3. Set up your database (see migration scripts or use your existing schema).
4. Set your web server's document root to `public/`.
5. Start the server and test endpoints.

## Endpoints
- `/api/auth/login`, `/api/auth/logout`
- `/api/trustees` (GET, POST, DELETE)
- `/api/donations` (GET), `/api/donations/order`, `/api/donations/verify`
- `/api/news` (GET, POST)
- `/api/inquiries` (GET, POST)
- `/api/admin/stats` (GET)

## Note
This is a starter structure. Implement business logic and database integration in controllers/models as needed.
