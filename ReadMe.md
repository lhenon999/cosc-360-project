# README

STEP 3) Minimal core functionality

Your site should have minimal core functionality and:

- [ ] Client-side security
- [ ] Posted on cosc360.ok.ubc.ca
- [ ] Server-side security
- [ ] Discussion thread stored in database
- [ ] Asynchronous updates
- [ ] Database functionality complete
- [ ] Core functional components operational (see baseline objectives)
- [ ] Preliminary summary document, indicating implemented functionalities
- [ ] Submit a link to your repo and a PDF document that describes what you have done regarding the requirements of this milestone.

Minimum Functional Requirements (for a C grade):

- [X] Hand-styled layout with contextual menus (i.e. when user has logged on to site, menus reflect change). Layout frameworks are not permitted other than Bootstrap
- [X] 2 or 3 column layout using appropriate design principles (i.e. highlighting nav links when hovered over, etc) responsive design
- [ ] Form validation with JavaScript
- [ ] Server-side scripting with PHP
- [X] Data storage in MySQL
- [ ] Appropriate security for data
- [X] Site must maintain state (user state being logged on, etc)
- [ ] Responsive design philosophy (minimum requirements for different non-mobile display sizes)
- [ ] AJAX (or similar) utilization for asynchronous updates (meaning that if a discussion thread is updated, another user who is viewing the same thread will not have to refresh the page to see the update)
- [x] User images (thumbnail) and profile stored in database
- [ ] Simple discussion (topics) grouping and display
- [ ] Navigation breadcrumb strategy (i.e. user can determine where they are in threads)
- [ ] Error handling (bad navigation)

Website user’s objectives:

- [X] Browse site without registering
- [X] Search for items/posts by keyword without registering
- [X] Register at the site by providing their name, e-mail and image
- [X] Allow user login by providing user id and password
- [ ] Create and comment (specific for each project) when logged into the site
- [ ] Users are required to be able to view/edit their profile

Website administrator’s objectives:

- [ ] Search for user by name, email or post
- [ ] Enable/disable users
- [ ] Edit/remove posts items or complete posts (project dependent)

Additional requirements:

- [ ] Search and analysis for topics/items
- [ ] Hot threads/hot item tracking
- [ ] Visual display of updates, etc (site usage charts, etc)
- [ ] Activity by date
- [ ] Tracking (including utilizing tracking API or your own with visualization tools)
- [ ] Collapsible items/treads without page reloading
- [ ] Alerts on page changes
- [ ] Admin view reports on usage (with filtering)
- [ ] Styling flourishes
- [ ] Responsive layout for mobile
- [ ] Tracking comment history from a user’s perspective
- [ ] Accessibility
  Your choice (this is your opportunity to add additional flourish to your site but will need to be documented in the final report)

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

### 2. Start the Web Server

#### For XAMPP:
Start the **Apache** server and **MySQL** server using XAMPP Control Panel

#### For MAMP:
Start the MAMP application and click "Start Servers"

The Stripe webhook forwarding will start automatically when you access any page.

### 3. Set File Permissions (macOS/Linux only)

```bash
chmod -R 755 /path/to/cosc-360-project/handmade_goods
chmod -R 777 /path/to/cosc-360-project/handmade_goods/logs
chmod -R 777 /path/to/cosc-360-project/handmade_goods/bin
```

### 4. Import Database

#### For XAMPP:
Open phpMyAdmin: `http://localhost/phpmyadmin`

#### For MAMP:
Open phpMyAdmin: `http://localhost:8888/phpMyAdmin`

Import the following SQL files in order:
1. `init.sql`
2. `populate_items.sql`
3. `populate_users.sql`

### 5. Test the Site

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
