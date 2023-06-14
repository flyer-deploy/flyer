#!/bin/bash

set -e

if [ "$DEBUG" == 1 ]; then
    set -x
fi

script_dir=$(dirname "$0")

# currently only supports Laravel

directory=""
filename=""
yaml_file=""

display_usage() {
    echo "Usage: $0 [-p <directory>] [-z <filename>] [-y <yaml_file>]"
    echo
    echo "Options:"
    echo "-p : Directory to create Laravel project"
    echo "-z : Zip filename"
    echo "-y : Yaml file"
    echo
    cat <<EOL
Example usage:
./artifact_creator.sh -p ./examples/larapel -z ./examples/larapel.zip -y /tmp/flyer.yaml
EOL
}

while getopts ":p:z:y:" opt; do
    case $opt in
    p) directory=$OPTARG ;;
    z) filename=$OPTARG ;;
    y) yaml_file=$OPTARG ;;
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

if [ -n "$yaml_file" ]; then
    cp "$yaml_file" "$directory"
fi

filename=$(readlink -f $filename)
cd "$directory" || exit
touch $directory/storage/some_random_file
zip -0 -r $filename .

echo "Laravel project created and zipped successfully!"
