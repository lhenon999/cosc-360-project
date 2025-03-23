<?php
class StripeCliManager {
    private $cli_path;
    private $webhook_pid_file;
    private $log_file;
    private $is_windows;
    private $webhook_url;
    private $api_key;

    public function __construct() {
        $this->is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->cli_path = $this->getCliPath();
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname(__DIR__) . '/logs';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0777, true);
        }
        
        $this->webhook_pid_file = $logs_dir . '/stripe_webhook.pid';
        $this->log_file = $logs_dir . '/stripe_cli.log';
        
        // Get base URL of the application
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base_url = $protocol . $host;
        
        // Default webhook URL
        $this->webhook_url = $base_url . "/cosc-360-project/handmade_goods/payments/stripe_webhook.php";
        
        // Use the API key from config if available, otherwise use the hardcoded one
        $this->api_key = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : 'sk_test_51R1OROBSlWUNcExMHHn87z0RghaVse7AkVdatLsQQdCZcP5KiBT4TRRjQcv22hUiDf5O1B09WX5FmG7QjX9MIgbp003xkLhNwH';
        
        $this->log("StripeCliManager initialized with webhook URL: {$this->webhook_url}");
    }

    private function getCliPath() {
        // Default installation paths for different OS
        if ($this->is_windows) {
            $paths = [
                getenv('LOCALAPPDATA') . '\\stripe\\stripe.exe',
                'C:\\Program Files\\stripe\\stripe.exe',
                'C:\\Users\\' . getenv('USERNAME') . '\\Downloads\\stripe_1.25.1_windows_x86_64\\stripe.exe', // Common download location
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
            // For Windows, use START command with hidden window and the /B option to run in background
            $cmd = "start /B /MIN \"\" {$this->cli_path} listen --api-key {$this->api_key} --forward-to {$this->webhook_url} > {$this->log_file} 2>&1";
            pclose(popen($cmd, 'r'));
            
            // Save some info to identify the process later
            file_put_contents($this->webhook_pid_file, time());
        } else {
            // For Unix systems, run in background and save PID
            $cmd = "{$this->cli_path} listen --api-key {$this->api_key} --forward-to {$this->webhook_url} > {$this->log_file} 2>&1 & echo $! > {$this->webhook_pid_file}";
            exec($cmd);
        }

        $this->log("Started webhook forwarder to {$this->webhook_url}");
        
        // Wait a moment to verify it started
        sleep(2);
        if ($this->isWebhookRunning()) {
            $this->log("Webhook service confirmed running");
            return true;
        } else {
            $this->log("WARNING: Failed to confirm webhook service is running");
            return false;
        }
    }

    public function stopWebhook() {
        if (!$this->isWebhookRunning()) {
            return true;
        }

        if ($this->is_windows) {
            exec("taskkill /F /IM stripe.exe 2>NUL");
            if (file_exists($this->webhook_pid_file)) {
                unlink($this->webhook_pid_file);
            }
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
            $running = !empty($output);
            
            // If it's running but we don't have a PID file, create one
            if ($running && !file_exists($this->webhook_pid_file)) {
                file_put_contents($this->webhook_pid_file, time());
            }
            
            return $running;
        } else {
            if (!file_exists($this->webhook_pid_file)) {
                return false;
            }
            $pid = trim(file_get_contents($this->webhook_pid_file));
            return $pid && file_exists("/proc/$pid");
        }
    }
    
    public function getStatus() {
        return [
            'is_running' => $this->isWebhookRunning(),
            'cli_path' => $this->cli_path,
            'webhook_url' => $this->webhook_url,
            'is_windows' => $this->is_windows,
            'last_log' => $this->getLastLogEntries(5)
        ];
    }
    
    private function getLastLogEntries($lines = 5) {
        if (!file_exists($this->log_file)) {
            return ["No log file found"];
        }
        
        $file = new SplFileObject($this->log_file, 'r');
        $file->seek(PHP_INT_MAX); // Seek to the end of file
        $last_line = $file->key(); // Get the last line number
        
        $entries = [];
        $start_line = max(0, $last_line - $lines);
        $file->seek($start_line);
        
        while (!$file->eof()) {
            $line = $file->current();
            if (trim($line) !== '') {
                $entries[] = trim($line);
            }
            $file->next();
        }
        
        return $entries;
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
}

// Function to get the singleton instance of StripeCliManager
function getStripeCliManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new StripeCliManager();
    }
    return $instance;
}

// Auto-start webhook forwarding if this file is included directly
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // If directly accessing this file, show status
    header('Content-Type: application/json');
    $action = $_GET['action'] ?? 'status';
    $manager = getStripeCliManager();
    
    $response = ['success' => true];
    
    switch ($action) {
        case 'start':
            $response['started'] = $manager->startWebhook();
            $response['status'] = $manager->getStatus();
            break;
        case 'stop':
            $response['stopped'] = $manager->stopWebhook();
            $response['status'] = $manager->getStatus();
            break;
        case 'status':
        default:
            $response['status'] = $manager->getStatus();
            break;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
} else {
    // When included in another file, just start the webhook if it's not running
    try {
        $manager = getStripeCliManager();
        if (!$manager->isWebhookRunning()) {
            $manager->startWebhook();
        }
    } catch (Exception $e) {
        error_log("Failed to start Stripe webhook forwarding: " . $e->getMessage());
    }
}