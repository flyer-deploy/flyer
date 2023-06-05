#!/bin/bash

script_dir=$(dirname "$0")

# currently only supports Laravel

directory=""
filename=""
appname=""

display_usage() {
    echo "Usage: $0 [-p <directory>] [-z <filename>]"
    echo
    echo "Options:"
    echo "-p : Directory to create Laravel project"
    echo "-z : Zip filename"
    echo
    cat <<EOL
Example usage:
./artifact_creator.sh -p ./examples/larapel -z ./examples/larapel.zip
EOL
}

while getopts ":p:z:" opt; do
    case $opt in
    p) directory=$OPTARG ;;
    z) filename=$OPTARG ;;
    \?)
        display_usage
        exit 1
        ;;
    esac
done

if [ -z "$directory" ] || [ -z "$filename" ]; then
    echo "Please provide both -p (directory) and -z (filename) options."
    display_usage
    exit 1
fi

composer_installed=$(command -v composer)
if [ -z "$composer_installed" ]; then
    echo "Composer is not installed. Please install Composer before running this script."
    exit 1
fi

composer create-project laravel/laravel "$directory"
cp $script_dir/flyer.toml $directory
cd "$directory" || exit

zip -0 -r "$filename" .

echo "Laravel project created and zipped successfully!"
