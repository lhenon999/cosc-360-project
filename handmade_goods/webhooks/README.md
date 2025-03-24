# Stripe Webhook Automation

This folder contains scripts that automate the Stripe webhook listener process. These scripts ensure that Stripe webhooks are properly forwarded to your application without requiring manual intervention.

## Files

- `stripe_webhook_manager.php`: Manages the Stripe webhook listener (start, stop, restart)
- `stripe_login.php`: Checks if Stripe CLI is logged in and helps with login if needed
- `autostart.php`: Automatically checks and starts the webhook listener when included
- `start_stripe_webhook.bat`: Windows batch file to start the webhook listener

## Setup Instructions

### One-time Setup (Required)

1. **Install Stripe CLI**: 
   - Download from [https://stripe.com/docs/stripe-cli](https://stripe.com/docs/stripe-cli)
   - Save the executable to a permanent location (e.g., `C:\Users\USER\Downloads\stripe_1.25.1_windows_x86_64\stripe.exe`)

2. **Log in to Stripe**:
   - Run the following command once: `stripe login`
   - Follow the prompts to authenticate with your Stripe account

3. **Configure the scripts**:
   - Open `stripe_webhook_manager.php` and update the `$stripeCliPath` variable with the correct path to your Stripe CLI executable
   - Open `stripe_login.php` and update the `$stripeCliPath` variable with the same path

### Automatic Startup (Windows)

#### Method 1: Task Scheduler (Recommended)

1. Open Windows Task Scheduler
2. Create a new task:
   - Name: "Stripe Webhook Listener"
   - Trigger: At system startup
   - Action: Start a program
   - Program/script: `C:\Windows\System32\cmd.exe`
   - Arguments: `/c "D:\Xampp\htdocs\cosc-360-project\handmade_goods\webhooks\start_stripe_webhook.bat"`
   - Start in: `D:\Xampp\htdocs\cosc-360-project\handmade_goods\webhooks`

#### Method 2: Startup Folder

1. Press `Win+R` to open the Run dialog
2. Type `shell:startup` and press Enter
3. Create a shortcut to the `start_stripe_webhook.bat` file in this folder

### Manual Control

You can manually control the webhook listener using these commands:

- Start: `php stripe_webhook_manager.php start`
- Stop: `php stripe_webhook_manager.php stop`
- Restart: `php stripe_webhook_manager.php restart`
- Check status: `php stripe_webhook_manager.php status`

## Troubleshooting

### Webhook Listener Not Starting

1. Check if Stripe CLI is properly installed and in the correct location
2. Verify that you're logged in to Stripe by running `stripe config`
3. Check the log files in the `../logs/` directory for error messages
4. Make sure your PHP executable path is correct in the batch file

### Login Issues

1. If the automatic login doesn't work, you can find the pairing code in `stripe_pairing_code.txt`
2. Run the Stripe CLI login command manually: `stripe login`
3. Enter the pairing code when prompted

## How It Works

1. The `autostart.php` script is included in your site's configuration file
2. When a user visits the site, it checks if the webhook listener is running
3. If not running, it starts the webhook listener in the background
4. The listener forwards webhook events to your webhook endpoint
5. Your application processes these events to handle Stripe payments

By setting up automatic startup using Task Scheduler, the webhook listener will start even before anyone visits your site. 