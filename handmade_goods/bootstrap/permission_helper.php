<?php
/**
 * Permission Helper - Automatically fixes permissions on Mac systems
 * This file is included in config.php and runs on every page load
 */

class PermissionHelper {
    
    /**
     * Check if critical directories are writable and fix if needed
     */
    public static function ensureMacPermissions() {
        // Only run on Mac OS
        if (stripos(PHP_OS, 'darwin') === false) {
            return;
        }
        
        $baseDir = dirname(__DIR__);
        $criticalDirs = [
            $baseDir . '/logs',
            $baseDir . '/assets/images/uploads',
            $baseDir . '/assets/images/uploads/product_images'
        ];
        
        $needsFix = false;
        
        // Check if any critical directories are not writable
        foreach ($criticalDirs as $dir) {
            if (file_exists($dir) && !is_writable($dir)) {
                $needsFix = true;
                break;
            }
            
            // Try to create if it doesn't exist
            if (!file_exists($dir)) {
                @mkdir($dir, 0777, true);
                if (!file_exists($dir) || !is_writable($dir)) {
                    $needsFix = true;
                    break;
                }
            }
        }
        
        // If permission issues detected, try to fix them
        if ($needsFix) {
            self::fixMacPermissions($baseDir);
        }
    }
    
    /**
     * Run the shell script to fix permissions
     */
    private static function fixMacPermissions($baseDir) {
        $scriptPath = $baseDir . '/bin/fix_mac_permissions.sh';
        
        // Make the script executable
        @chmod($scriptPath, 0755);
        
        // Try to run the script
        if (is_executable($scriptPath)) {
            $cmd = "bash " . escapeshellarg($scriptPath) . " " . escapeshellarg($baseDir) . " 2>&1";
            @shell_exec($cmd);
        }
        
        // As a fallback, try direct chmod commands
        @shell_exec("chmod -R 777 " . escapeshellarg($baseDir . "/logs") . " 2>/dev/null");
        @shell_exec("chmod -R 777 " . escapeshellarg($baseDir . "/assets/images/uploads") . " 2>/dev/null");
    }
}
?>