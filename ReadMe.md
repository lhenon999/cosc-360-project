# README

## Setup

#### Requirements:

- **XAMPP** (Apache, MySQL, PHP) for Windows/Linux or **MAMP** for macOS
- **PHP 7.4+** with `curl` extension enabled
- **Internet connection** for initial Stripe CLI download

### 1. Move the Project to the Web Server Directory:

#### For XAMPP (Windows/Linux):
Place the project folder `handmade_goods` inside:
- Windows: `C:\xampp\htdocs\cosc-360-project`
- Linux: `/opt/lampp/htdocs/cosc-360-project`

#### For MAMP (macOS):
Place the project folder in:
- `/Applications/MAMP/htdocs/cosc-360-project`

### 2. Run the Setup Script:

**IMPORTANT:** After cloning the repository, you should run the setup script to ensure all required directories have the correct permissions:

1. Start your web server
2. Navigate to `http://localhost/cosc-360-project/handmade_goods/setup_directories.php`
3. Follow any instructions provided by the setup script to fix permission issues

This script will automatically create and set permissions for essential directories like `logs` and `temp` that are needed for the application to function correctly.

### 3. Start the Web Server

#### For XAMPP:
Start the **Apache** server and **MySQL** server using XAMPP Control Panel

#### For MAMP:
Start the MAMP application and click "Start Servers"

The Stripe webhook forwarding will start automatically when you access any page.

### 4. Set File Permissions (macOS/Linux only)

```bash
chmod -R 755 /path/to/cosc-360-project/handmade_goods
chmod -R 777 /path/to/cosc-360-project/handmade_goods/logs
chmod -R 777 /path/to/cosc-360-project/handmade_goods/bin
```

### 5. Import Database

#### For XAMPP:
Open phpMyAdmin: `http://localhost/phpmyadmin`

#### For MAMP:
Open phpMyAdmin: `http://localhost:8888/phpMyAdmin`

Import the following SQL files in order:
1. `init.sql`
2. `populate_items.sql`
3. `populate_users.sql`

### 6. Test the Site

#### For XAMPP:
Open: `http://localhost/cosc-360-project/handmade_goods/pages/home.php`

#### For MAMP:
Open: `http://localhost:8888/cosc-360-project/handmade_goods/pages/home.php`

### Test Accounts

Normal user:
- Email: 'johndoe@mail.com'
- Password: 'John@123'

Admin user:
- Email: 'admin@handmadegoods.com'
- Password: 'Admin@123'

### Test Payments

Use any of these test card numbers:
- Success: 4242 4242 4242 4242
- Requires Authentication: 4000 0025 0000 3155
- Payment Declined: 4000 0000 0000 9995

For all test cards:
- Any future expiration date
- Any 3 digits for CVC
- Any postal code

### Webhook Forwarding

The system will automatically:
1. Check if Stripe CLI is installed
2. Download and install it if needed
3. Start webhook forwarding when the site is accessed
4. Handle stopping and restarting of webhooks automatically

You can check the webhook status in the logs at:
`/logs/stripe_cli.log`

### Troubleshooting

1. **Webhook Not Starting:**
   - Check the logs in `logs/stripe_cli.log`
   - Ensure PHP has permissions to execute commands
   - On macOS/Linux, ensure the Stripe CLI binary is executable

2. **Permission Issues (macOS/Linux):**
   - Run the chmod commands in section 3
   - Ensure your web server user has write access to logs and bin directories

3. **Port Conflicts:**
   - Default webhook uses port 3000
   - If port 3000 is in use, edit stripe_cli_manager.php to use a different port
