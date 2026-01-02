# Plaza Management System

A comprehensive web-based management system for commercial plazas, designed to manage shops, rooms, basements, lease agreements, payments, ledger, and maintenance requests.

## Features

### Admin Features
- **Dashboard**: Overview of all plaza operations with interactive charts and key statistics
- **Spaces Management**: Unified view of all spaces (shops, rooms, basements) with assignment capabilities
- **Settings**: Manage shop, room, and basement configurations
- **Customer Management**: Add, edit, and manage customer records (clients, not system users)
- **Space Assignment**: Assign spaces to customers with automatic agreement and invoice generation
- **Assigned Spaces**: View all assigned spaces with complete details, edit assignments, download agreements, and view invoices
- **Agreements**: Automatically created when assigning spaces, with document upload and print functionality
- **Ledger System**: Track all financial transactions (rent, security deposit, expenses, etc.)
- **Payment Management**: Record payments with support for combined payments (rent + security deposit), receipt uploads, and pending balance tracking
- **Customer Details**: Comprehensive customer view with personal info, financial summary, assigned spaces, agreements, ledger, and payment history
- **Reports**: Generate financial, customer, lease, and maintenance reports with date filtering
- **Profile Management**: Update admin profile (name and password)

### Key Features
- **Automatic Agreement Generation**: Agreements are created automatically when assigning spaces to customers
- **Combined Payments**: Support for paying rent and security deposit together in a single transaction
- **Partial Payments**: Allow customers to make partial payments during space assignment
- **Invoice Generation**: Automatic invoice creation with print functionality
- **Print Functionality**: Print agreements, invoices, and customer details reports
- **Currency**: Pakistani Rupees (Rs) throughout the system
- **Responsive Design**: Modern left sidebar navigation with mobile support

## Technology Stack

- **Backend**: PHP (Procedural)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Custom responsive CSS with Flexbox/Grid layouts
- **Icons**: Font Awesome
- **Charts**: Chart.js (for dashboard visualizations)
- **Currency**: Pakistani Rupees (Rs)

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

4. **Configure Base URL (Optional)**:
   - The system auto-detects the base URL from the server
   - For manual override, edit `config/config.php` and set BASE_URL before the getBaseUrl() function
   - For online deployment, the system will automatically use HTTPS if available

5. **Run setup script**:
   - Navigate to: `http://localhost/plaza_ms/setup.php`
   - This will create the default admin user
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`
   - **IMPORTANT**: Change the password after first login!

6. **Set up file permissions** (Linux/Mac):
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 config/
   ```

7. **Access the application**:
   - Open browser and navigate to: `http://localhost/plaza_ms/`
   - Login with the admin credentials created in step 5

## Online Deployment

### For Production Server:

1. **Upload files** to your web server (via FTP, cPanel File Manager, or Git)

2. **Database setup**:
   - Create database on your hosting server
   - Import `database.sql` file
   - Update `config/database.php` with production database credentials

3. **Base URL**:
   - The system automatically detects the base URL from the server
   - No manual configuration needed - it will use HTTPS if available
   - For subdomain/subfolder installations, the path is auto-detected

4. **File permissions** (Linux servers):
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 config/
   chmod 644 .htaccess
   ```

5. **Security**:
   - Uncomment HTTPS redirect in `.htaccess` if you have SSL certificate
   - Change default admin password immediately
   - Ensure `config/database.php` has proper file permissions (not publicly accessible)

6. **Access**:
   - Navigate to your domain: `https://yourdomain.com/plaza_ms/` (or root if installed there)
   - Login with admin credentials

## File Structure

```
plaza_ms/
├── admin/                      # Admin panel pages
│   ├── dashboard.php          # Dashboard with charts and statistics
│   ├── spaces.php             # View and assign all spaces
│   ├── assigned-spaces.php    # View all assigned spaces with details
│   ├── customers.php          # Customer management
│   ├── customer-details.php   # Detailed customer view
│   ├── settings.php           # Manage shops, rooms, basements
│   ├── ledger.php             # Financial ledger management
│   ├── payments.php           # Payment management with pending balances
│   ├── reports.php            # Generate various reports
│   ├── profile.php            # Admin profile management
│   ├── assign-space.php       # Handle space assignment
│   ├── update-assignment.php  # Update existing assignments
│   ├── unassign-space.php     # Unassign spaces
│   ├── print-agreement.php    # Print agreement
│   ├── print-invoice.php      # Print invoice
│   ├── get-invoices.php       # AJAX endpoint for invoices
│   └── add-customer-ajax.php  # AJAX endpoint for adding customers
├── assets/                     # Static assets
│   ├── css/
│   │   └── style.css          # Main stylesheet with responsive design
│   └── js/
│       └── main.js            # Main JavaScript for interactivity
├── config/                     # Configuration files
│   ├── config.php             # Global configuration and helper functions
│   └── database.php           # Database connection
├── includes/                   # Common includes
│   ├── header.php             # Header with sidebar navigation
│   └── footer.php             # Footer
├── uploads/                    # Uploaded files
│   ├── agreements/            # Agreement documents
│   └── receipts/              # Payment receipts
├── database.sql                # Database schema
├── index.php                   # Home page (redirects to login)
├── login.php                   # Standalone login page
├── logout.php                  # Logout handler
└── README.md                   # This file
```

## Database Schema

The system uses the following main tables:
- `users` - System user accounts (admin only, customers are not system users)
- `customers` - Customer/client records (plaza tenants/clients)
- `shops` - Shop spaces
- `rooms` - Room spaces
- `basements` - Basement spaces
- `agreements` - Lease agreements (automatically created on space assignment)
- `ledger` - Financial transactions (rent, security deposit, expenses, etc.)
- `payments` - Payment records with receipt uploads
- `maintenance_requests` - Maintenance requests
- `notifications` - System notifications

### Key Relationships
- `customers` table stores client information (separate from system users)
- `agreements` reference `customers.customer_id` (not `users.user_id`)
- `ledger` and `payments` reference `customers.customer_id`
- Spaces (shops, rooms, basements) can be assigned to customers via `customer_id`

## Usage

### Admin Login
1. Navigate to the login page
2. Enter admin credentials (default: `admin` / `admin123`)
3. Access the admin dashboard

### Managing Customers
1. Go to **Customers** in admin panel
2. Click **Add Customer** to create a new customer record
3. Fill in customer details (name, phone, CNIC, address, etc.)
4. Save

### Assigning a Space to Customer
1. Go to **Spaces** in admin panel
2. Find the space you want to assign
3. Click **Assign to Customer**
4. Select existing customer or create new customer directly from the modal
5. Fill in agreement details (start date, end date, monthly rent, security deposit)
6. Optionally make partial payment during assignment
7. Upload agreement document (optional)
8. Save - Agreement and initial ledger entries are created automatically

### Viewing Assigned Spaces
1. Go to **Assigned Spaces** in admin panel
2. View all currently assigned spaces with complete details
3. Use actions to:
   - Edit assignment details
   - View/Download agreement
   - View invoices
   - View customer details

### Recording a Payment
1. Go to **Payments** in admin panel
2. View pending/remaining balances at the top
3. Click **Pay Now** on any pending balance, or click **Record Payment**
4. Select customer and items to pay (can select multiple items for combined payment)
5. Enter payment details (amount, method, transaction ID)
6. Upload receipt (optional)
7. Save - Ledger entries are automatically updated to 'paid' status

### Viewing Customer Details
1. Go to **Customers** in admin panel
2. Click **View Details** on any customer
3. View comprehensive information including:
   - Personal information
   - Financial summary (Security Deposit, Total Paid, Pending, Overdue)
   - Assigned spaces
   - Agreements with print options
   - Ledger entries
   - Payment history
4. Use **Print** button to generate a printable customer report

### Generating Reports
1. Go to **Reports** in admin panel
2. Select report type (Financial, Customer, Lease, Maintenance)
3. Set date range
4. Click **Generate**
5. View detailed report with statistics and data tables

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

## Recent Updates

- **Customer Management System**: Separated customers (clients) from system users
- **Automatic Agreement Generation**: Agreements created automatically when assigning spaces
- **Combined Payments**: Support for paying multiple items (rent + security deposit) in one transaction
- **Partial Payments**: Allow partial payments during space assignment with automatic balance tracking
- **Enhanced Dashboard**: Interactive charts and visual statistics
- **Print Functionality**: Print agreements, invoices, and customer reports
- **Modern UI**: Left sidebar navigation, responsive design, professional styling
- **Table Scrolling**: Tables scroll horizontally within containers without moving the page

## Version

Version 2.0.0 - Major Update
- Customer management system
- Automatic agreement generation
- Combined payment support
- Enhanced reporting and printing

