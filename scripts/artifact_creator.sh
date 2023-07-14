#!/bin/bash

set -e

if [ "$DEBUG" == 1 ]; then
    set -x
fi

# currently only supports Laravel

directory=""
filename=""
yaml_file=""
with_composer_create_project=0
clean_first=0

display_usage() {
    echo "Usage: $0 [-p <directory>] [-z <filename>] [-y <yaml_file>] -c"
    echo
    echo "Options:"
    echo "-p : Directory to create Laravel project"
    echo "-z : Zip filename"
    echo "-y : Yaml file"
    echo "-c : With composer create project"
    echo "-d : Delete (clean) target directory and zip filename"
    echo
    cat <<EOL
Example usage:
./examples/artifact_creator.sh -p ./examples/larapel -z ./examples/larapel.zip -y ./examples/flyer.yaml -c 1 -d 1
EOL
}

while getopts ":p:z:y:c:d:" opt; do
    case $opt in
    p) directory=$OPTARG ;;
    z) filename=$OPTARG ;;
    y) yaml_file=$OPTARG ;;
    c) with_composer_create_project=1 ;;
    d) clean_first=1 ;;
    \?)
        display_usage
        exit 1
        ;;
    esac
done

if [ -n "$directory" ]; then
    directory=$(readlink -f $directory)
fi

if [ -n "$filename" ]; then
    filename=$(readlink -f $filename)
fi

if [ -n "$directory" ] && [ -n "$filename" ] && [ $clean_first == 1 ]; then
    echo "Deleting target project directory and zip file"
    rm -rf $directory
    rm -f $filename
fi

if [ -z "$directory" ]; then
    echo "Please provide -p (directory) option."
    display_usage
    exit 1
fi

composer_installed=$(command -v composer)
if [ -z "$composer_installed" ] && [ $with_composer_create_project == 1 ]; then
    echo "Composer is not installed. Please install Composer before running this script."
    exit 1
fi

if [ $with_composer_create_project == 1 ]; then
    composer create-project laravel/laravel "$directory"
fi

if [ -n "$yaml_file" ]; then
    cp "$yaml_file" "$directory"
fi

cd "$directory" || exit

if [ -n "$filename" ]; then
    zip -q -0 -r $filename .
fi
