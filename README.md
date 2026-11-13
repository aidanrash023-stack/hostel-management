# Hostel Management System

A PHP and MySQL admin website for managing hostel residents
and administrator accounts.

## Features

- Admin login and logout
- Dashboard with resident and admin totals
- Add, view, edit and delete residents
- Add, view, edit and delete admin accounts
- Password hashing
- Prepared statements
- CSRF protection for form submissions

## Requirements

- XAMPP with Apache and MySQL
- PHP 8 or later
- A web browser

## Installation

1. Copy the hostel_management folder into the XAMPP htdocs folder.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin.
4. Create a database named hostel_management if it does not exist.
5. Select the database and import database/hostel_management.sql.
6. Check the database settings in db_connect.php.
7. Open http://localhost/hostel_management/.
8. Log in using the demo admin credentials supplied separately.

## Technologies

- PHP
- MySQL
- HTML
- CSS
- JavaScript