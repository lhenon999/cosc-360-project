#!/bin/bash
# Mac permission fixer script for COSC-360 project
# This script is automatically called when permission issues are detected

# Get the base directory from the command line argument
BASEDIR="$1"

if [ -z "$BASEDIR" ]; then
    echo "Error: No directory specified"
    exit 1
fi

# Ensure the directory exists
if [ ! -d "$BASEDIR" ]; then
    echo "Error: Directory does not exist: $BASEDIR"
    exit 1
fi

# Fix permissions for critical directories
chmod -R 777 "$BASEDIR/logs" 2>/dev/null
chmod -R 777 "$BASEDIR/assets/images/uploads" 2>/dev/null
chmod -R 777 "$BASEDIR/assets/images/uploads/product_images" 2>/dev/null
chmod -R 777 "$BASEDIR/temp" 2>/dev/null
chmod -R 777 "$BASEDIR/bin" 2>/dev/null

echo "Permissions fixed for $BASEDIR"
exit 0