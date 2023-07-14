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

    # https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo >&2 'ERROR: Invalid installer checksum'
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php
    exit $RESULT
fi

# Use Composer to install Deployer
composer global require deployer/deployer
composer_config_list=$(composer global config --list | tail -n 1)
composer_packages_bin=${composer_config_list:7}

cat <<EOL
Run this command to add \`dep\` binary to \$PATH:

    echo "export PATH=\$PATH:$composer_packages_bin/vendor/bin" >> ~/.bashrc
    source ~/.bashrc

Run this command if you want all users to be able to run \`dep\`:

    sudo ln -s $(which dep) /usr/local/bin

EOL
