# README

STEP 3) Minimal core functionality

Your site should have minimal core functionality and:

- [x] Client-side security
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
- [x] Form validation with JavaScript
- [x] Server-side scripting with PHP
- [X] Data storage in MySQL
- [ ] Appropriate security for data
- [X] Site must maintain state (user state being logged on, etc)
- [ ] Responsive design philosophy (minimum requirements for different non-mobile display sizes)
- [ ] AJAX (or similar) utilization for asynchronous updates (meaning that if a discussion thread is updated, another user who is viewing the same thread will not have to refresh the page to see the update)
- [x] User images (thumbnail) and profile stored in database
- [ ] Simple discussion (topics) grouping and display
- [ ] Navigation breadcrumb strategy (i.e. user can determine where they are in threads)
- [x] Error handling (bad navigation)

Website user’s objectives:

- [X] Browse site without registering
- [X] Search for items/posts by keyword without registering
- [X] Register at the site by providing their name, e-mail and image
- [X] Allow user login by providing user id and password
- [ ] Create and comment (specific for each project) when logged into the site
- [x] Users are required to be able to view/edit their profile

Website administrator’s objectives:

- [x] Search for user by name, email or post
- [ ] Enable/disable users
- [ ] Edit/remove posts items or complete posts (project dependent)

Additional requirements:

- [x] Search and analysis for topics/items
- [ ] Hot threads/hot item tracking
- [ ] Visual display of updates, etc (site usage charts, etc)
- [ ] Activity by date
- [ ] Tracking (including utilizing tracking API or your own with visualization tools)
- [ ] Collapsible items/treads without page reloading
- [ ] Alerts on page changes
- [ ] Admin view reports on usage (with filtering)
- [x] Styling flourishes
- [ ] Responsive layout for mobile
- [ ] Tracking comment history from a user’s perspective
- [x] Accessibility
  Your choice (this is your opportunity to add additional flourish to your site but will need to be documented in the final report)

## Setup

#### Requirements:

- **XAMPP** (Apache, MySQL, PHP)

### 1. Move the Project to the XAMPP Directory:

Place the project folder `handmade_goods` inside the **htdocs** directory.

`C:\xampp\htdocs\handmade_goods`

---

### 2. Start XAMPP

Start the **Apache** server and **MySQL** server

---

### 3. Import db

Open **phpMyAdmin**:
`http://localhost/phpmyadmin`

Import `init.sql`, `populate_items.sql`, `populate_users.sql`

---

### 4. Update config.php and test site

`http://localhost/cosc-360-project/handmade_goods/pages/home.php`

login info:

normal user:

user ID: 'johndoe@mail.com'
password: 'John@123'

admin user:

user ID: 'admin@handmadegoods.com'
password: 'Admin@123'

---

Paypal Sandbox API account info:

email: sb-r282p34425608@business.example.com

password: V>C6HtE7
