# MotoRepair Pro - Motorcycle Repair Shop Management System

## 📋 Overview

MotoRepair Pro is a comprehensive web-based management system designed for motorcycle repair shops. It streamlines the entire workflow from customer booking to repair completion, with dedicated portals for customers, mechanics, and administrators.

## ✨ Features

### 👤 Customer Portal
- **Service Booking**: Easy online booking with motorcycle details
- **Track Status**: Real-time tracking of repair progress using booking ID
- **Booking History**: View all past and current bookings
- **Queue Number**: Automatic queue assignment for fair service ordering

### 🔧 Mechanic Portal
- **Dashboard**: Overview of assigned repairs with statistics
- **My Repairs**: List of all assigned repairs with status filtering
- **Work on Repair**: Update repair status, add notes, and track parts used
- **Performance Metrics**: View completion rates and earnings

### 👑 Admin Portal
- **Dashboard**: Comprehensive overview with revenue charts and statistics
- **Job Management**: View and update all bookings status
- **Assign Mechanics**: Assign pending repairs to available mechanics
- **Inventory Management**: Add, edit, and manage spare parts
- **Mechanic Management**: Add, edit, and delete mechanics
- **Customer Management**: View and manage customer records
- **Reports & Analytics**: Generate detailed reports on revenue, services, and performance

## 🏗️ System Architecture

```
MotoRepair Pro/
├── admin/                    # Admin Panel
│   ├── assign-mechanic.php
│   ├── customer-details.php
│   ├── customers.php
│   ├── dashboard.php
│   ├── generate-invoice.php
│   ├── inventory.php
│   ├── jobs.php
│   ├── mechanic-performance.php
│   ├── mechanics.php
│   ├── repair-details.php
│   └── reports.php
├── customer/                 # Customer Portal
│   ├── book-service.php
│   ├── booking-status.php
│   ├── home.php
│   └── track-status.php
├── mechanic/                 # Mechanic Portal
│   ├── dashboard.php
│   ├── my-repairs.php
│   └── work-on-repair.php
├── config/                   # Configuration
│   └── database.php
├── includes/                 # Core Includes
│   └── auth.php
├── assets/                   # Static Assets
│   ├── css/
│   ├── js/
│   └── images/
├── phpmailer/               # Email Library
├── index.php                # Landing Page
├── login.php               # Authentication
├── register.php            # User Registration
├── logout.php              # Logout Handler
├── process-booking.php     # Booking Processor
├── send-notification.php   # Email Notifications
└── motorcycle_repair_shop.sql  # Database Schema
```

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.1.3
- **Database**: MySQL 5.7+
- **Libraries**: 
  - Font Awesome 6.0 (Icons)
  - Chart.js (Analytics)
  - PHPMailer (Email Notifications)
- **Authentication**: Session-based with password hashing

## 📦 Database Schema

### Key Tables:
- **users**: System users (admin, mechanic, customer)
- **bookings**: Service booking records
- **repairs**: Repair job details
- **parts**: Inventory parts
- **repair_parts**: Parts used in repairs
- **notifications**: System notifications

## 🚀 Installation Guide

### Prerequisites
- XAMPP/WAMP/MAMP or any PHP-enabled server
- MySQL Database
- PHP 7.4 or higher

### Step 1: Clone/Download
```bash
git clone https://github.com/yourusername/motorepair-pro.git
# or download the ZIP file and extract
```

### Step 2: Database Setup
1. Open phpMyAdmin or MySQL CLI
2. Create a new database:
```sql
CREATE DATABASE motorepair_db;
```
3. Import the database schema:
```sql
USE motorepair_db;
SOURCE motorcycle_repair_shop.sql;
```

### Step 3: Configure Database Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'motorepair_db');
```

### Step 4: Configure Email (Optional)
Edit `phpmailer/` configuration for email notifications.

### Step 5: Run the Application
1. Place the project in your web server root (e.g., `htdocs/motorepair/`)
2. Start Apache and MySQL
3. Access: `http://localhost/motorepair/`

## 🔐 Default Credentials

### Admin Access
- **Email**: `admin@motorepair.com`
- **Password**: `admin123`

### Mechanic Access
- **Email**: `mechanic@motorepair.com`
- **Password**: `mechanic123`

### Customer Access
- **Email**: `customer@motorepair.com`
- **Password**: `customer123`

*Note: You can register new accounts or create users through the admin panel.*

## 📱 User Roles & Permissions

| Feature | Customer | Mechanic | Admin |
|---------|----------|----------|-------|
| Book Service | ✅ | ❌ | ❌ |
| Track Status | ✅ | ❌ | ❌ |
| View Own Bookings | ✅ | ❌ | ❌ |
| View Assigned Repairs | ❌ | ✅ | ❌ |
| Update Repair Status | ❌ | ✅ | ❌ |
| Manage Mechanics | ❌ | ❌ | ✅ |
| Manage Inventory | ❌ | ❌ | ✅ |
| Assign Mechanics | ❌ | ❌ | ✅ |
| View Reports | ❌ | ❌ | ✅ |

## 🎯 Core Workflow

1. **Customer** registers and books a service
2. System assigns a queue number
3. **Admin** assigns a mechanic to the repair
4. **Mechanic** works on the repair, updates status
5. **Customer** tracks progress online
6. **Admin** generates invoice and completes the job
7. **System** sends email notifications at each step

## 📊 Reports Available

- **Daily/Weekly Revenue**: Track income over time
- **Service Type Performance**: Popular services and revenue
- **Mechanic Performance**: Completion rates and earnings
- **Top Customers**: Most valuable customers
- **Inventory Status**: Stock levels and popular parts

## 🔧 Customization

### Adding New Service Types
Modify the `service_type` values in the `bookings` table or update the dropdown in `book-service.php`.

### Adding New Specializations
Update the specialization options in `admin/mechanics.php` and the database.

### Modifying Theme Colors
Update CSS variables in the `:root` section of each page:
```css
:root {
    --primary-color: #e67e22;
    --primary-dark: #c8641a;
    --ink: #16171a;
    /* ... */
}
```

## 🔒 Security Features

- Password hashing using `password_hash()`
- Session-based authentication
- Role-based access control
- Input sanitization and validation
- Prepared statements to prevent SQL injection
- XSS protection with `htmlspecialchars()`

## 📧 Email Notifications

The system sends emails for:
- Booking confirmation
- Status updates
- Invoice generation
- Assignment notifications

Configure `send-notification.php` for custom email templates.

## 🐛 Troubleshooting

### Database Connection Error
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check if database name is correct

### Login Issues
- Clear browser cookies and cache
- Check if user exists in database
- Verify password hash matches

### Email Not Working
- Configure SMTP settings in PHPMailer
- Check email server requirements
- Verify recipient email addresses

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support, email info@motorepair.com or visit our support portal.

## 🙏 Acknowledgments

- Bootstrap team for the amazing framework
- Font Awesome for the icons
- Chart.js for analytics charts
- PHPMailer for email functionality

---

**MotoRepair Pro** - Professional Motorcycle Repair Management System