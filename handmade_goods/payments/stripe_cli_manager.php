<?php
class StripeCliManager {
    private $cli_path;
    private $webhook_pid_file;
    private $log_file;
    private $is_windows;

    public function __construct() {
        $this->is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->cli_path = $this->getCliPath();
        $this->webhook_pid_file = dirname(__DIR__) . '/logs/stripe_webhook.pid';
        $this->log_file = dirname(__DIR__) . '/logs/stripe_cli.log';
        
        if (!is_dir(dirname($this->log_file))) {
            mkdir(dirname($this->log_file), 0777, true);
        }
    }

    private function getCliPath() {
        // Default installation paths for different OS
        if ($this->is_windows) {
            $paths = [
                getenv('LOCALAPPDATA') . '\\stripe\\stripe.exe',
                'C:\\Program Files\\stripe\\stripe.exe',
                dirname(__DIR__) . '\\bin\\stripe.exe'
            ];
        } else {
            $paths = [
                '/usr/local/bin/stripe',
                '/usr/bin/stripe',
                getenv('HOME') . '/.stripe/stripe',
                dirname(__DIR__) . '/bin/stripe'
            ];
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // If not found, download and install
        return $this->installCli();
    }

    private function installCli() {
        $install_dir = dirname(__DIR__) . ($this->is_windows ? '\\bin' : '/bin');
        if (!is_dir($install_dir)) {
            mkdir($install_dir, 0777, true);
        }

        $cli_path = $install_dir . ($this->is_windows ? '\\stripe.exe' : '/stripe');

        // Download URL based on OS and architecture
        $arch = PHP_INT_SIZE === 8 ? 'x64' : 'x86';
        $os = $this->is_windows ? 'windows' : (PHP_OS === 'Darwin' ? 'mac' : 'linux');
        $url = "https://github.com/stripe/stripe-cli/releases/latest/download/stripe_{$os}_{$arch}";
        if ($this->is_windows) $url .= '.exe';

        $this->log("Downloading Stripe CLI from: $url");
        
        // Use appropriate download method
        if ($this->is_windows) {
            file_put_contents($cli_path, file_get_contents($url));
        } else {
            exec("curl -L $url -o $cli_path");
            chmod($cli_path, 0755);
        }

        return $cli_path;
    }

    public function startWebhook() {
        if ($this->isWebhookRunning()) {
            $this->log("Webhook is already running");
            return true;
        }

        $webhook_secret = 'whsec_6a9b6dbb671c8fc8cf891c8c609112daa6ccc51bcef292a40895b2566fd379ae';
        
        // Build command based on OS
        if ($this->is_windows) {
            $cmd = "start /B {$this->cli_path} listen --api-key sk_test_51R1OROBSlWUNcExMHHn87z0RghaVse7AkVdatLsQQdCZcP5KiBT4TRRjQcv22hUiDf5O1B09WX5FmG7QjX9MIgbp003xkLhNwH --forward-to http://localhost/cosc-360-project/handmade_goods/payments/stripe_webhook.php";
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = "{$this->cli_path} listen --api-key sk_test_51R1OROBSlWUNcExMHHn87z0RghaVse7AkVdatLsQQdCZcP5KiBT4TRRjQcv22hUiDf5O1B09WX5FmG7QjX9MIgbp003xkLhNwH --forward-to http://localhost/cosc-360-project/handmade_goods/payments/stripe_webhook.php > {$this->log_file} 2>&1 & echo $! > {$this->webhook_pid_file}";
            exec($cmd);
        }

        $this->log("Started webhook forwarder");
        return true;
    }

    public function stopWebhook() {
        if (!$this->isWebhookRunning()) {
            return true;
        }

        if ($this->is_windows) {
            exec("taskkill /F /IM stripe.exe 2>NUL");
        } else {
            $pid = trim(file_get_contents($this->webhook_pid_file));
            if ($pid) {
                exec("kill $pid 2>/dev/null");
                unlink($this->webhook_pid_file);
            }
        }

        $this->log("Stopped webhook forwarder");
        return true;
    }

    public function isWebhookRunning() {
        if ($this->is_windows) {
            exec("tasklist | findstr /I \"stripe.exe\"", $output);
            return !empty($output);
        } else {
            if (!file_exists($this->webhook_pid_file)) {
                return false;
            }
            $pid = trim(file_get_contents($this->webhook_pid_file));
            return $pid && file_exists("/proc/$pid");
        }
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
}

// Auto-start webhook forwarding when this script is included
try {
    $manager = new StripeCliManager();
    if (!$manager->isWebhookRunning()) {
        $manager->startWebhook();
    }
} catch (Exception $e) {
    error_log("Failed to start Stripe webhook forwarding: " . $e->getMessage());
}