#!/bin/bash

# Check if PHP is installed
if ! command -v php &>/dev/null; then
    echo "PHP is not installed. Please install PHP and run the script again."
    exit 1
fi

# Install Composer if not already installed
if ! command -v composer &>/dev/null; then
    temp=$(mktemp -d)
    cd "$temp" || exit 1
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    sudo mv composer.phar /usr/local/bin/composer
fi

# Use Composer to install Deployer
composer global require deployer/deployer
composer_config_list=$(composer config --list | tail -n 1)
composer_packages_bin=${composer_config_list:7}

cat <<EOL
Run this command to add \`dep\` binary to \$PATH:

    echo "export PATH=\$PATH:$composer_packages_bin/vendor/bin" >> ~/.bashrc
    source ~/.bashrc

EOL
