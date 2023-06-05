#!/bin/sh

composer_bin_path=$(composer -n config --global home)/vendor/bin

export PATH=$PATH:$composer_bin_path

artifact_file=""
deploy_path=""

echo "$@"

display_usage() {
    echo "Usage: $0 [-a <artifact_file>] [-d <deploy_path>]"
    echo "Options:"
    echo "-a : Artifact file"
    echo "-d : Deploy path"
}

while getopts ":a:d:" opt; do
    case $opt in
    a) artifact_file=$OPTARG ;;
    d) deploy_path=$OPTARG ;;
    \?)
        display_usage
        exit 1
        ;;
    esac
done

if [ -z "$artifact_file" ] || [ -z "$deploy_path" ]; then
    echo "Please provide both -a (artifact file) and -d (deploy path) options."
    display_usage
    exit 1
fi

# run the
DEPLOY_PATH=$deploy_path ARTIFACT_FILE=$artifact_file dep -f src/recipes/flyer.php deploy
