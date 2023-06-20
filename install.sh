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

# # Add dep alias to root
# if ! grep -q "alias dep='dep'" ~/.bashrc; then
#     echo "Adding 'dep' alias to root..."
#     echo "alias dep='dep'" | sudo tee -a ~/.bashrc
#     source ~/.bashrc
#     echo "'dep' alias added to root successfully."
# fi

# # Add dep alias to current user
# if ! grep -q "alias dep='dep'" ~/.bashrc; then
#     echo "Adding 'dep' alias to current user..."
#     echo "alias dep='dep'" >> ~/.bashrc
#     source ~/.bashrc
#     echo "'dep' alias added to current user successfully."
# fi

# echo "Script execution completed."