# Restaurant Management System

This project is a full-stack restaurant management system built with PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap. It includes both a customer-facing website and a staff-facing dashboard for day-to-day restaurant operations.

The application is designed to demonstrate an end-to-end workflow for a modern restaurant, including menu presentation, reservations, billing, kitchen coordination, account management, and business reporting.

## Modules

### Customer Side

- Browse the restaurant menu
- Register and log in as a customer
- View profile and membership information
- Make table reservations
- Download or view reservation receipts

### Staff and Admin Side

- Staff login
- Point-of-sale billing
- Add and remove bill items
- Kitchen order handling
- Cash and card payment processing
- Revenue statistics and printable reports
- Manage menu items, tables, reservations, customers, staff, and accounts

## Project Structure

- `index.php`
  Entry page for setup and access to customer and staff modules.
- `restaurantDB.txt`
  Database schema and seed data for the project.
- `customerSide/`
  Customer website pages, reservation flow, login system, and styling.
- `adminSide/`
  Staff dashboard, POS module, CRUD operations, reports, and analytics.

## Database Design

The project uses a relational MySQL schema with separate tables for:

- `Accounts`
- `Staffs`
- `Memberships`
- `Reservations`
- `Restaurant_Tables`
- `Table_Availability`
- `Bills`
- `Bill_Items`
- `Kitchen`
- `Menu`
- `card_payments`

This project now uses stronger database design than the original version:

- normalized table separation for accounts, staff, memberships, reservations, billing, and menu items
- foreign keys to maintain relationships
- unique constraints for reservation slots and bill item combinations
- check constraints for safer data validation
- transactions in multi-step account, reservation, billing, and payment flows
- concurrency control for reservation and POS operations using transactions, locks, and guarded updates

## Current Demo Data

The seeded demo data has been cleaned and updated for presentation purposes:

- sample dates are set to March and April 2026
- staff, customer, and membership names use Indian-style names
- account emails and phone numbers have been normalized
- menu pricing has been updated to premium 5-star hotel pricing
- the menu now includes Indian starters, tandoori dishes, curries, biryanis, naan and rice items, South Indian dishes, and premium desserts

## Key Features

### Reservations

- customer and admin reservation flows
- table availability handling
- duplicate-slot protection with explicit concurrency control
- reservation receipt generation

### POS and Billing

- create bills from the staff dashboard
- add items to bills
- update cart quantities safely
- process cash and card payments
- generate printable receipts

### Reporting and Statistics

- revenue summary for today, this week, this month, and total revenue
- payment method charts
- most purchased items charts
- printable report generation

## Tech Stack

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap
- Google Charts
- FPDF

## How to Run

1. Make sure PHP and MySQL are installed on your machine.
2. Start the MySQL server.
3. Start the PHP development server from the project root:

```bash
php -S 127.0.0.1:8001
```

4. Open the project in the browser:

```text
http://127.0.0.1:8001
```

5. On first run, the project can initialize the database using `restaurantDB.txt`.
6. If setup was already completed earlier, the app uses the existing database and shows the landing page.

## Configuration

Database connection settings are defined in:

- `adminSide/config.php`
- `customerSide/config.php`

The database used by the project is `restaurantdb` / `restaurantDB` depending on local MySQL case behavior.

## Example Login Details

### Staff Login

- Staff ID: `1`
- Password: `password123`

### Admin Verification

- Admin ID: `99999`
- Password: `12345`

## Academic Value

This project is suitable for academic demonstration because it combines:

- frontend and backend integration
- relational database design
- CRUD operations
- transaction handling
- concurrency control
- reporting and analytics
- realistic restaurant workflow coverage

## Notes

- MySQL must be running for database-backed pages to work correctly.
- The project contains seeded sample data intended for demo and review use.
- The application is a strong academic and portfolio project base and can be extended further with deployment, role-based access control, API integration, or cloud migration.
