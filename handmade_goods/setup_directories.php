<?php
/**
 * Directory Setup Script
 * 
 * This script checks and creates required directories with appropriate permissions.
 * Run this script after cloning the repository to ensure all necessary directories exist.
 */

// Disable error display but log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Define required directories
$requiredDirs = [
    'logs',
    'logs/stripe',
    'logs/inventory',
    'bin',
    'temp'
];

// Base path
$basePath = __DIR__;

// Check and create each directory
$results = [];
foreach ($requiredDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    $result = [
        'path' => $fullPath,
        'status' => 'unknown',
        'message' => '',
        'writable' => false
    ];
    
    // Check if directory exists
    if (file_exists($fullPath)) {
        if (is_dir($fullPath)) {
            $result['status'] = 'exists';
            $result['message'] = 'Directory already exists';
            
            // Check if writable
            if (is_writable($fullPath)) {
                $result['writable'] = true;
            } else {
                $result['message'] .= ' but is not writable';
                
                // Try to make it writable
                if (chmod($fullPath, 0777)) {
                    $result['message'] .= '. Made writable.';
                    $result['writable'] = true;
                } else {
                    $result['message'] .= '. Failed to make writable.';
                }
            }
        } else {
            $result['status'] = 'error';
            $result['message'] = 'Path exists but is not a directory';
        }
    } else {
        // Try to create directory
        if (mkdir($fullPath, 0777, true)) {
            $result['status'] = 'created';
            $result['message'] = 'Directory created successfully';
            $result['writable'] = true;
        } else {
            $result['status'] = 'error';
            $result['message'] = 'Failed to create directory';
        }
    }
    
    $results[$dir] = $result;
}

// Create a test file in each writable directory
foreach ($results as $dir => $result) {
    if ($result['writable']) {
        $testFile = $result['path'] . '/test_file.txt';
        $testContent = 'This is a test file created by setup_directories.php on ' . date('Y-m-d H:i:s');
        
        if (file_put_contents($testFile, $testContent)) {
            $results[$dir]['file_test'] = 'passed';
            // Clean up
            unlink($testFile);
        } else {
            $results[$dir]['file_test'] = 'failed';
        }
    }
}

// Output HTML or JSON based on request
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Display HTML report
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory Setup for Handmade Goods</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .directory-item {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .exists {
            background-color: #ecfdf5;
            border-left: 5px solid #10b981;
        }
        .created {
            background-color: #f0fdf4;
            border-left: 5px solid #22c55e;
        }
        .error {
            background-color: #fef2f2;
            border-left: 5px solid #ef4444;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background-color: #f3f4f6;
            border-radius: 5px;
        }
        .actions {
            margin-top: 20px;
        }
        button, .button {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .button:hover {
            background-color: #4338ca;
        }
    </style>
</head>
<body>
    <h1>Directory Setup for Handmade Goods</h1>
    <p>This tool checks and creates required directories with appropriate permissions for the Handmade Goods application.</p>
    
    <h2>Directory Status</h2>
    <?php foreach ($results as $dir => $result): ?>
        <div class="directory-item <?= $result['status'] ?>">
            <h3><?= $dir ?></h3>
            <p><strong>Path:</strong> <?= $result['path'] ?></p>
            <p><strong>Status:</strong> <?= $result['status'] ?></p>
            <p><strong>Message:</strong> <?= $result['message'] ?></p>
            <p><strong>Writable:</strong> <?= $result['writable'] ? 'Yes' : 'No' ?></p>
            <?php if (isset($result['file_test'])): ?>
                <p><strong>File Write Test:</strong> <?= $result['file_test'] === 'passed' ? 'Passed' : 'Failed' ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <div class="summary">
        <h2>Summary</h2>
        <?php
        $allSuccess = true;
        foreach ($results as $result) {
            if ($result['status'] === 'error' || !$result['writable']) {
                $allSuccess = false;
                break;
            }
        }
        ?>
        
        <?php if ($allSuccess): ?>
            <p>✅ All directories are properly set up and writable. The application should work correctly.</p>
        <?php else: ?>
            <p>❌ There are issues with some directories. The application may not function correctly.</p>
            <div class="fix-instructions">
                <h3>How to fix permission issues:</h3>
                <h4>For Windows:</h4>
                <p>Ensure your web server has write permissions to the following directories:</p>
                <pre>
<?php foreach ($requiredDirs as $dir): ?>
<?= realpath(__DIR__ . '/' . $dir) ?: __DIR__ . '/' . $dir ?>\
<?php endforeach; ?>
                </pre>
                <p>If using XAMPP, you may need to run it as administrator.</p>
                
                <h4>For macOS/Linux:</h4>
                <p>Run these commands in your terminal:</p>
                <pre>
chmod -R 777 <?= __DIR__ ?>/logs
chmod -R 777 <?= __DIR__ ?>/bin
chmod -R 777 <?= __DIR__ ?>/temp
                </pre>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="actions">
        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="button">Run Check Again</a>
        <a href="check_stripe.php" class="button">Check Stripe Setup</a>
        <a href="pages/home.php" class="button">Go to Homepage</a>
    </div>
</body>
</html> 