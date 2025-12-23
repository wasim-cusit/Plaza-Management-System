# Plaza Management System

A comprehensive web-based management system for commercial plazas, designed to manage shops, rooms, basements, lease agreements, payments, ledger, and maintenance requests.

## Features

### Admin Features
- **Dashboard**: Overview of all plaza operations with key statistics
- **Shop Management**: Add, edit, delete, and manage shop spaces
- **Room Management**: Manage office/meeting room spaces
- **Basement Management**: Handle parking and storage spaces
- **Agreement Management**: Create and manage lease agreements with document upload
- **Ledger System**: Track all financial transactions
- **Payment Management**: Record and track payments with receipt uploads
- **Maintenance Management**: Track and resolve maintenance requests
- **Tenant Management**: Add, edit, and manage tenant accounts
- **Reports**: Generate financial, tenant, lease, and maintenance reports

### Tenant Features
- **Dashboard**: Personal overview with statistics
- **My Agreements**: View and download lease agreements
- **My Ledger**: View financial transaction history
- **My Payments**: View payment history and download receipts
- **Maintenance Requests**: Submit and track maintenance requests
- **Profile**: Update personal information

## Technology Stack

- **Backend**: PHP (Procedural)
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript
- **UI Framework**: Custom responsive CSS with Font Awesome icons

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/LAMP (for local development)

## Installation

1. **Clone or extract the project** to your web server directory:
   ```
   C:\xampp\htdocs\plaza_ms\ (Windows)
   /var/www/html/plaza_ms/ (Linux)
   ```

2. **Create the database**:
   - Open phpMyAdmin or MySQL command line
   - Import the `database.sql` file to create the database and tables
   - Or run: `mysql -u root -p < database.sql`

3. **Configure database connection**:
   - Edit `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'plaza_ms');
     ```

4. **Run setup script**:
   - Navigate to: `http://localhost/plaza_ms/setup.php`
   - This will create the default admin user
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`
   - **IMPORTANT**: Change the password after first login!

5. **Set up file permissions** (Linux/Mac):
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 config/
   ```

6. **Access the application**:
   - Open browser and navigate to: `http://localhost/plaza_ms/`
   - Login with the admin credentials created in step 4

## File Structure

```
plaza_ms/
├── admin/              # Admin panel pages
│   ├── dashboard.php
│   ├── shops.php
│   ├── rooms.php
│   ├── basements.php
│   ├── agreements.php
│   ├── ledger.php
│   ├── payments.php
│   ├── maintenance.php
│   ├── reports.php
│   └── tenants.php
├── tenant/             # Tenant panel pages
│   ├── dashboard.php
│   ├── agreements.php
│   ├── ledger.php
│   ├── payments.php
│   ├── maintenance.php
│   └── profile.php
├── assets/             # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/             # Configuration files
│   ├── config.php
│   └── database.php
├── includes/           # Common includes
│   ├── header.php
│   └── footer.php
├── uploads/            # Uploaded files
│   ├── agreements/
│   └── receipts/
├── database.sql        # Database schema
├── index.php           # Home page
├── login.php           # Login page
├── logout.php          # Logout handler
├── profile.php         # Profile redirect
└── README.md           # This file
```

## Database Schema

The system uses the following main tables:
- `users` - Admin and tenant accounts
- `shops` - Shop spaces
- `rooms` - Room spaces
- `basements` - Basement spaces
- `agreements` - Lease agreements
- `ledger` - Financial transactions
- `payments` - Payment records
- `maintenance_requests` - Maintenance requests
- `notifications` - System notifications

## Usage

### Admin Login
1. Navigate to the login page
2. Enter admin credentials
3. Access the admin dashboard

### Creating a Tenant
1. Go to **Tenants** in admin panel
2. Click **Add Tenant**
3. Fill in tenant details
4. Save

### Creating an Agreement
1. Go to **Agreements** in admin panel
2. Click **Create Agreement**
3. Select tenant and space
4. Fill in agreement details
5. Upload agreement document (optional)
6. Save

### Recording a Payment
1. Go to **Payments** in admin panel
2. Click **Record Payment**
3. Select tenant and related agreement/ledger entry
4. Enter payment details
5. Upload receipt (optional)
6. Save

### Submitting Maintenance Request (Tenant)
1. Login as tenant
2. Go to **Maintenance**
3. Click **Submit Request**
4. Fill in issue details
5. Submit

## Security Notes

- Change default admin password immediately
- Use strong passwords for all accounts
- Keep PHP and MySQL updated
- Implement SSL/HTTPS for production
- Regularly backup the database
- Restrict file upload permissions

## Customization

### Changing Colors
Edit `assets/css/style.css` and modify CSS variables:
```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #10b981;
    /* ... */
}
```

### Adding Features
- All database operations are in individual page files
- Follow existing code patterns for consistency
- Use prepared statements for database queries

## Support

For issues or questions:
1. Check database connection settings
2. Verify file permissions
3. Check PHP error logs
4. Ensure all required PHP extensions are enabled

## License

This project is provided as-is for educational and commercial use.

## Version

Version 1.0.0 - Initial Release

