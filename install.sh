#!/bin/bash

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP and run the script again."
    exit 1
fi

# Install Deployer
if ! command -v dep &> /dev/null; then
    echo "Installing Deployer..."
    curl -LO https://deployer.org/deployer.phar
    sudo mv deployer.phar /usr/local/bin/dep
    sudo chmod +x /usr/local/bin/dep
    echo "Deployer installed successfully."
fi