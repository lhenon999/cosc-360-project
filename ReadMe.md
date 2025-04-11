# README

#### Requirements:

- **XAMPP** (Apache, MySQL, PHP) for Windows/Linux
- **PHP 7.4+** with `curl` extension enabled

## Setup

### 1. Move the Project to the Web Server Directory

### 2. Start the Web Server

### 5. Import Database

Import the following files in order:
1. `init.sql`
2. `populate_items.sql`
3. `populate_users.sql`
4. `generate_sales.sql`
4. `generate_reviews.sql`

### 6. Test the Site

Open: `http://localhost/cosc-360-project/handmade_goods/pages/home.php`

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

