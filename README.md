# Customer Management and Balance Management System

A complete, professional, production-ready **Customer Management and Balance Management System** built with **Core PHP**, **MySQL**, **PDO**, **HTML5**, **CSS3**, **Bootstrap 5**, **JavaScript**, and **AJAX**.

Designed specifically for optical retail stores, clinics, and customer-focused businesses to track customer orders, optical prescriptions (Power, Lens Type, Frame Type), advance payments, live balance calculations, date range financial reports, and printable statements.

---

## Key Features

- **Secure PHP Authentication**: Password hashing via `password_hash()` (BCrypt), session fixation protection, and secure logout.
- **Dynamic Header & Overview Stats**: Dynamic header total balance badge (`Total Balance: ₹XXXXX`), total customer counter, and financial summary cards.
- **Customer Directory**:
  - Add customers with optical entry.
  - Automatic mobile lookup & duplicate prevention (attaches new entries to existing customer profiles automatically).
  - Calculated financial totals per customer (`Total Price = SUM(price)`, `Total Advanced = SUM(advanced)`, `Total Balance = Total Price - Total Advanced`).
- **Dynamic Dropdown Management**:
  - **Power Management**: Add, edit, delete, activate, deactivate optical powers (`+1.00`, `-2.50`, etc.).
  - **Lens Type Management**: Add, edit, delete, activate, deactivate lens types (`Single Vision`, `Progressive`, `Blue Cut`, etc.).
  - **Frame Type Management**: Add, edit, delete, activate, deactivate frame types (`Metal`, `Plastic`, `Rimless`, etc.).
  - Customer entry forms load **active** dropdown options dynamically from MySQL.
- **Real-Time Client & Server Balance Calculation**:
  - Client-side real-time calculation as user types Price and Advanced payment: `Balance = Price - Advanced`.
  - Advanced payment validation (`Advanced` cannot exceed `Price`).
  - Strict re-calculation on PHP server before saving to MySQL.
- **Advance Payment Management & Audit Log**:
  - Record additional payments against customer entries.
  - Auto-updates entry balance and status (`Paid` if balance = 0, `Pending` if balance > 0).
  - Full Payment History audit log table.
- **Reports & Printer-Friendly Statements**:
  - Customer Summary Report.
  - Date Range Report (`From Date` & `To Date` filters with printable layout).
  - Outstanding Pending Balance Report.
  - Clean, printer-friendly Customer Statement page (`print/statement.php`).
- **Admin Profile & Password Security**:
  - Update username and email address.
  - Change password with current password verification and BCrypt re-hashing.

---

## Default Admin Login Credentials

- **URL**: `http://localhost/customer-management/`
- **Username**: `admin` (or `admin@example.com`)
- **Password**: `admin123`

---

## Step-by-Step Installation Instructions for XAMPP

### Step 1: Install XAMPP
Download and install [XAMPP for Windows](https://www.apachefriends.org/index.html) with PHP 8.0+ and MySQL / MariaDB.

### Step 2: Start Apache and MySQL
Open the **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.

### Step 3: Copy Project Files
Place the project folder into your XAMPP `htdocs` directory:
```text
C:\xampp\htdocs\customer-management\
```

### Step 4: Open phpMyAdmin
Open your web browser and navigate to:
```text
http://localhost/phpmyadmin/
```

### Step 5: Import Database Schema & Seed Data
1. Click **Databases** tab and create a new database named:
   ```text
   customer_management
   ```
2. Click on the `customer_management` database in the left sidebar.
3. Click the **Import** tab at the top.
4. Click **Choose File** and select `database/database.sql` from your project directory:
   ```text
   C:\xampp\htdocs\customer-management\database\database.sql
   ```
5. Click **Import** (or **Go**) at the bottom of the page.

*Alternatively, using MySQL CLI in Command Prompt:*
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "source C:/xampp/htdocs/customer-management/database/database.sql"
```

### Step 6: Verify Database Configuration
Check `config/database.php` to ensure your database connection settings match your XAMPP MySQL setup:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'customer_management');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 7: Launch Application
Open your browser and navigate to:
```text
http://localhost/customer-management/
```
Log in using:
- **Username**: `admin`
- **Password**: `admin123`

---

## Financial Calculation Logic & Formulas

1. **Individual Entry Balance**:
   $$\text{Balance} = \max(0, \text{Price} - \text{Advanced})$$

2. **Entry Payment Status**:
   $$\text{Status} = \begin{cases} \text{Paid} & \text{if } \text{Balance} = 0 \\ \text{Pending} & \text{if } \text{Balance} > 0 \end{cases}$$

3. **Customer Aggregate Totals**:
   $$\text{Total Price} = \sum \text{Entry Prices}$$
   $$\text{Total Advanced} = \sum \text{Entry Advances}$$
   $$\text{Total Balance} = \text{Total Price} - \text{Total Advanced}$$

4. **Recording Additional Payments**:
   $$\text{New Advanced} = \text{Existing Advanced} + \text{Payment Amount}$$
   $$\text{New Balance} = \text{Price} - \text{New Advanced}$$

---

## File Structure

```text
customer-management/
├── index.php                 # Root redirect to admin/dashboard.php
├── README.md                 # Complete documentation & XAMPP guide
├── config/
│   └── database.php          # PDO Singleton database connection
├── includes/
│   ├── auth.php              # Session checks, CSRF validation, authorization
│   ├── functions.php         # Core system helpers, query functions, flash alerts
│   ├── header.php            # Admin top navbar with balance summary badge
│   ├── sidebar.php           # Responsive sidebar menu items
│   └── footer.php            # Footer, Bootstrap 5 JS & script injection
├── assets/
│   ├── css/
│   │   └── style.css         # Modern, custom CSS styling & print layout rules
│   └── js/
│       └── script.js         # Real-time balance calculations & AJAX handlers
├── admin/
│   ├── login.php             # Secure admin login
│   ├── logout.php            # Secure logout
│   ├── dashboard.php         # Analytics cards & recent customer entries
│   └── profile.php           # Edit username, email & password
├── customers/
│   ├── index.php             # Customer list with search, filter & pagination
│   ├── add.php               # Form to add customer & entry
│   ├── edit.php              # Edit customer name and mobile
│   ├── view.php              # Customer statement, entry list & payment modal
│   ├── delete.php            # Safe deletion with cascade handling
│   └── pending.php           # Pending balance list
├── entries/
│   ├── index.php             # Global entries table with search & filter
│   ├── add.php               # Add new customer entry (with mobile auto-lookup)
│   ├── edit.php              # Edit entry with live calculation
│   ├── view.php              # Single entry view & payment history log
│   └── delete.php            # Delete entry
├── payments/
│   ├── add.php               # Record additional payment handler
│   └── history.php           # Global payment audit log
├── dropdowns/
│   ├── powers.php            # Optical Power options management
│   ├── lens-types.php        # Lens Type options management
│   └── frame-types.php       # Frame Type options management
├── reports/
│   ├── customers.php         # Customer summary report
│   ├── dates.php             # Date range financial report
│   └── balances.php          # Outstanding balance report
├── print/
│   └── statement.php         # Printer-friendly customer statement
├── api/
│   └── ajax.php              # AJAX endpoints for mobile check & payments
└── database/
    └── database.sql          # Seed script with schema & default admin credentials
```
