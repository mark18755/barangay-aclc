# Barangay ACLC — Complaint Management System
## Setup Instructions

### Requirements
- XAMPP (Apache + MySQL + PHP 8+)
- VS Code

### Step 1 — Import Database
1. Start Apache & MySQL in XAMPP Control Panel
2. Go to: http://localhost/phpmyadmin
3. Click **Import** tab
4. Select `database.sql` → Click **Go**

### Step 2 — Configure DB (if needed)
Edit `api/config.php`:
```php
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');        // your MySQL password
```

### Step 3 — Place files
Copy folder to: `C:\xampp\htdocs\barangay-aclc\`

### Step 4 — Open
Visit: http://localhost/barangay-aclc/

---

## Login Accounts
| Email                       | Password   | Role  |
|-----------------------------|------------|-------|
| admin@barangay.gov.ph       | admin123   | Admin |
| staff@barangay.gov.ph       | staff123   | Staff |

---

## Features
- Public website: Home, Features, About, Contact
- Login with email + password (no username)
- Create Account (pending approval by admin)
- Forgot Password (verify by email + contact number)
- Dashboard with live stats
- New Complaint form
- Blotter Records (searchable)
- Reports (filterable + printable)
- Manage Accounts (approve, disable, change role, delete)
- Inbox — view messages sent from Contact Us form
- My Profile + Change Password
