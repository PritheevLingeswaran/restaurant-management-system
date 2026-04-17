# Restaurant Management System

This project is a PHP and MySQL based restaurant management system with both customer-facing and staff-facing modules. It is designed to support restaurant operations such as menu browsing, table reservations, point-of-sale billing, kitchen order tracking, customer account management, and sales reporting.

## Project Overview

The application is divided into two main areas:

- `customerSide`
  Customer website for browsing the restaurant, registering accounts, logging in, viewing profile details, and making reservations.
- `adminSide`
  Staff and admin panel for handling POS billing, kitchen workflow, reservation management, menu updates, tables, customers, staff accounts, and reports.

## Main Features

### Customer Features

- Browse menu items by category
- Register and log in to customer accounts
- View membership/profile information
- Make restaurant reservations
- View reservation receipt/details

### Staff and Admin Features

- Staff login and access control
- Create and manage bills through the POS module
- Add and remove bill items
- Send orders to the kitchen
- Process cash and card payments
- Generate printable receipts
- Manage menu items, tables, reservations, customers, and staff
- View revenue statistics and reports

## Technology Stack

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap

## Project Structure

- `index.php`
  Entry point for initial setup and application access.
- `restaurantDB.txt`
  SQL schema and seed data used to initialize the database.
- `customerSide/`
  Customer-facing pages and assets.
- `adminSide/`
  Admin and staff dashboard, POS, reports, and CRUD modules.

## Database

The project uses a MySQL database named `restaurantDB` or `restaurantdb` depending on the setup path already present in the codebase. The schema includes tables for:

- accounts
- staffs
- memberships
- restaurant tables
- table availability
- reservations
- menu
- bills
- bill items
- kitchen orders
- card payments

## How To Run

1. Make sure PHP and MySQL are installed.
2. Start your MySQL server.
3. Place the project in a PHP-served directory, or run it with the PHP built-in server:

```bash
php -S 127.0.0.1:8001
```

4. Open the project in your browser:

```text
http://127.0.0.1:8001
```

5. If the project has not been set up yet, it will use `restaurantDB.txt` to create and seed the database.
6. If setup was already completed before, delete `setup_completed.flag` only if you intentionally want to rerun the setup process.

## Example Accounts

| Role | Username / Email | Password |
|---|---|---|
| Customer | dadsvawvid@gmail.com | david4pass |
| Customer | zoe@gmail.com | passworddef |
| Customer | jackie@gmail.com | passwordstu |
| Staff | 1 | password123 |
| Staff | 10 | davidpa2ss |
| Staff | 7 | robertpass |
| Admin | 99999 | 12345 |

## Notes

- Some modules expect MySQL to be running before the project is opened.
- Database connection settings can be changed in:
  - `adminSide/config.php`
  - `customerSide/config.php`
- The project includes seeded menu, account, reservation, and billing data for testing.

## Purpose

This project demonstrates a full restaurant workflow system that combines customer interaction with back-office operations in a single codebase. It can be used as an academic project, portfolio project, or starting point for a more complete restaurant platform.
