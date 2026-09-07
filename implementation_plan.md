# Implementation Plan - Customer Management and Balance Management System

A comprehensive, production-ready **Customer Management and Balance Management System** built with **Core PHP**, **MySQL**, **PDO**, **Bootstrap 5**, **JavaScript**, and **AJAX**. This system allows an optical / retail business admin to manage customers, customer optical entries, dynamic dropdown attributes (Power, Lens Type, Frame Type), advance payments, pending balances, dynamic reports, and printable customer statements.

---

## User Review Required

> [!IMPORTANT]
> - **Database Configuration**: Default configuration targets localhost MySQL (`127.0.0.1:3306`, user `root`, no password, database `customer_management`).
> - **Default Admin Credentials**: Username `admin` (or `admin@example.com`), Password `admin123` (hashed via `password_hash()` in seed SQL).
> - **Indian Rupee Formatting**: Currency amounts are formatted as `₹` with locale number formatting (`₹5,000.00`).

---

## Architecture & Database Design

### 1. Database Schema (`customer_management`)

```sql
-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Powers table
CREATE TABLE IF NOT EXISTS powers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lens Types table
CREATE TABLE IF NOT EXISTS lens_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Frame Types table
CREATE TABLE IF NOT EXISTS frame_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer Entries table
CREATE TABLE IF NOT EXISTS customer_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    power_id INT NULL,
    lens_type_id INT NULL,
    frame_type_id INT NULL,
    entry_date DATE NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    advanced DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Paid', 'Pending') NOT NULL DEFAULT 'Pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (power_id) REFERENCES powers(id) ON DELETE SET NULL,
    FOREIGN KEY (lens_type_id) REFERENCES lens_types(id) ON DELETE SET NULL,
    FOREIGN KEY (frame_type_id) REFERENCES frame_types(id) ON DELETE SET NULL,
    INDEX idx_entry_date (entry_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_entry_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    notes VARCHAR(255) NULL,
    created_by VARCHAR(50) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_entry_id) REFERENCES customer_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Core Features & File Structure

```text
customer-management/
├── index.php                 # Root redirect to admin/dashboard.php
├── config/
│   └── database.php          # PDO Singleton connection & helper
├── includes/
│   ├── auth.php              # Session checks, CSRF validation, authorization
│   ├── functions.php         # Core business calculations, query helpers, flash messages, escaping
│   ├── header.php            # Modern top navbar, balance summary badge, user profile menu
│   ├── sidebar.php           # Collapsible admin navigation menu
│   └── footer.php            # Scripts, copyright, toast notifications container
├── assets/
│   ├── css/
│   │   └── style.css         # Modern, custom aesthetic styling & print stylesheets
│   ├── js/
│   │   └── script.js         # Real-time calculations, AJAX handlers, modal logic
├── admin/
│   ├── login.php             # Secure admin authentication with password_verify()
│   ├── logout.php            # Destroys session securely
│   ├── dashboard.php         # Analytics cards, recent entries, pending customers table
│   └── profile.php           # Edit username, email, change password
├── customers/
│   ├── index.php             # Customer list with search, filter, pagination & aggregate totals
│   ├── add.php               # Add customer & entry combined form
│   ├── edit.php              # Edit customer info (Name, Mobile)
│   ├── view.php              # Customer details, financial history, entry list & print button
│   ├── delete.php            # Safe deletion confirmation and cascade/handling
│   └── pending.php           # Customers with pending balance (> 0)
├── entries/
│   ├── index.php             # Global entries list with filters (date, power, lens, frame, status)
│   ├── add.php               # Add entry (auto-associates duplicate mobile or creates new customer)
│   ├── edit.php              # Edit entry (re-calculates balance & payment status)
│   ├── view.php              # Entry detail view with payment breakdown
│   └── delete.php            # Delete entry with safe financial updates
├── payments/
│   ├── add.php               # Record payment modal / standalone handler
│   └── history.php           # Complete payment audit log
├── dropdowns/
│   ├── powers.php            # Manage optical powers (add, edit, delete, toggle status)
│   ├── lens-types.php        # Manage lens types (add, edit, delete, toggle status)
│   └── frame-types.php       # Manage frame types (add, edit, delete, toggle status)
├── reports/
│   ├── customers.php         # Comprehensive Customer financial report
│   ├── dates.php             # Date range financial report (From Date / To Date totals)
│   └── balances.php          # Outstanding balances report with print view
├── print/
│   └── statement.php         # Printer-friendly customer statement
├── api/
│   └── ajax.php              # AJAX endpoints for live search, live customer mobile check, quick payment modal
└── database/
    └── database.sql          # Seed script with default admin, powers, lens types, frame types, sample data
```

---

## Key Technical Specifications & Calculation Business Rules

1. **Calculations**:
   - Entry Balance: `Balance = Price - Advanced`.
   - Recalculated both client-side via JavaScript on keyup/change and strictly validated server-side on submit.
   - Entry Status: If `Balance <= 0` => `Paid`, else `Pending`.
   - Financial Totals for Customer:
     - `Total Price = SUM(price)` for all entries of customer.
     - `Total Advanced = SUM(advanced)` for all entries of customer.
     - `Total Balance = Total Price - Total Advanced`.
2. **Customer Auto-Merge by Mobile**:
   - When creating a customer entry, if a customer with the entered `mobile` already exists, attach the entry to that existing customer's `customer_id`. Do NOT duplicate customer records.
3. **Payments**:
   - Recording a new payment inserts a row into `payments`, adds `amount` to `customer_entries.advanced`, and updates `customer_entries.balance = price - advanced`.
   - Prevents `advanced` from exceeding `price`.
4. **Security**:
   - PDO prepared statements for ALL database queries.
   - CSRF token validation on all POST actions.
   - Input sanitization (`filter_var`, regex validation for mobile number).
   - Password hashing with `password_hash(PASSWORD_BCRYPT)` and validation with `password_verify()`.
   - Session authentication check on all protected pages.
5. **Modern UI Aesthetics**:
   - Responsive Bootstrap 5 theme with soft shadows, glassmorphism badges, metric cards, crisp typography (Inter font), AJAX live filter search, and toast alerts.

---

## Verification Plan

### Automated & CLI Testing
- Initialize MySQL database using `database/database.sql` via `C:\xampp\mysql\bin\mysql.exe`.
- Verify database tables, indexes, constraints, and initial admin record.
- Run `php -l` on all created PHP files to verify zero syntax errors.

### Browser & End-to-End Testing (via `browser_subagent` and local PHP web server)
1. Start local PHP server: `php -S 127.0.0.1:8085`
2. Test Admin Login with default credentials (`admin` / `admin123`).
3. Verify Dashboard metric cards, total balances, recent entries.
4. Test Dynamic Dropdowns (Powers, Lens Types, Frame Types):
   - Add new items, toggle active/inactive status, edit item names.
5. Test Customer Entry Creation & Real-time Balance Calculation:
   - Add entry for Customer A (Price: ₹5000, Advanced: ₹2000, verify client-side and server-side Balance = ₹3000).
   - Add second entry for Customer A with same mobile (Price: ₹3000, Advanced: ₹1000). Verify customer total is Price: ₹8000, Advanced: ₹3000, Balance: ₹5000.
6. Test Record Payment:
   - Record payment of ₹2000 on Entry 1. Verify Advanced becomes ₹4000, Balance becomes ₹1000.
   - Record payment of ₹1000 on Entry 1. Verify Advanced becomes ₹5000, Balance becomes ₹0, Status changes to `Paid`.
7. Test Pending Balances & Reports (Date filter report, Customer report, Balance report).
8. Test Printer-friendly Customer Statement page.
9. Test Admin Profile update & Password Change.
10. Test Safe Deletion & CSRF protections.
