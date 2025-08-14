# Loan Automate (LA)

A comprehensive microfinance management system built with PHP and MySQL, designed to handle savings accounts, loans, and financial transactions.

## Features

- **User Management**
  - Multi-role system (Admin, Agent, Customer)
  - Secure authentication and authorization
  - User profile management
  - Account status control

- **Savings Management**
  - Deposit and withdrawal transactions
  - Real-time balance tracking
  - Transaction history
  - Printable receipts

- **Loan Management**
  - Loan applications and approvals
  - Risk assessment
  - Automated repayment scheduling
  - Loan status tracking
  - Configurable interest rates

- **Admin Dashboard**
  - System-wide statistics
  - User activity monitoring
  - Transaction reports
  - Loan approval workflow
  - System settings management

- **Security Features**
  - Password hashing
  - SQL injection prevention
  - XSS protection
  - Session management
  - CSRF protection

- **Additional Features**
  - Maintenance mode
  - Email notifications
  - Data export (CSV)
  - Responsive design
  - Print-friendly receipts

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled
- PHP Extensions:
  - PDO
  - MySQLi
  - mbstring
  - json
  - session

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/easy-savings-system.git
   ```

2. Create a MySQL database and import the schema:
   ```bash
   mysql -u your_username -p your_database < database/schema.sql
   ```

3. Configure the database connection:
   - Copy `includes/database.example.php` to `includes/database.php`
   - Update the database credentials

4. Set up the web server:
   - Point the document root to the `public` directory
   - Ensure the storage directory is writable:
     ```bash
     chmod -R 755 storage/
     ```

5. Create the first admin user:
   ```sql
   INSERT INTO users (full_name, email, password, role, status) 
   VALUES ('Admin User', 'admin@example.com', 'hashed_password', 'admin', 'active');
   ```

## Directory Structure

```
ess/
├── admin/              # Admin panel files
├── includes/           # Core PHP classes and functions
├── templates/          # Reusable template files
├── assets/            # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── img/
├── storage/           # Uploaded files and logs
├── database/         # Database schema and migrations
└── public/           # Public files
```

## Security Considerations

1. **File Permissions**
   - Set appropriate file permissions
   - Protect sensitive files from direct access

2. **Configuration**
   - Keep configuration files outside web root
   - Use environment variables for sensitive data

3. **Database**
   - Use prepared statements
   - Implement input validation
   - Regular backups

## Usage

1. **Admin Panel**
   - Access: `http://your-domain.com/admin/`
   - Manage users, loans, and system settings
   - Generate reports and monitor activity

2. **Customer Portal**
   - Access: `http://your-domain.com/`
   - View account balance and transactions
   - Apply for loans and make repayments

3. **Agent Portal**
   - Process customer transactions
   - Handle loan applications
   - Generate receipts

## Customization

1. **Theme**
   - Modify `assets/css/custom.css`
   - Update Bootstrap variables in `assets/scss/`

2. **Email Templates**
   - Edit templates in `templates/emails/`

3. **System Settings**
   - Configure through admin panel
   - Update default values in settings table

## Maintenance

1. **Database Backup**
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Log Files**
   - Regular cleanup of `storage/logs/`
   - Implement log rotation

3. **Updates**
   - Check for security updates
   - Test in staging environment
   - Follow upgrade instructions

## Support

For support and bug reports:
- Create an issue on GitHub
- Email: support@example.com
- Documentation: `/docs`

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Credits

- Bootstrap 5
- Font Awesome
- PHP MySQLi
- Chart.js
- DataTables

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Roadmap

- Mobile app integration
- API development
- Multi-language support
- Advanced reporting
- Payment gateway integration 