<?php

namespace TheRecipes;

use Yosymfony\Toml\Toml;

function mandatory($value, $key)
{
    if (empty($value)) {
        throw new \Deployer\Exception\ConfigurationException("Please specify $key");
    }
    return $value;
}

function mkdir_if_not_exists(string $dir)
{
    \Deployer\run("test -d $dir || mkdir $dir");
}

function get_config_from_artifact(string $artifact_dir)
{
    $files = glob($artifact_dir . 'flyer.toml');
    $config = null;

    if (empty($files)) {
        echo "Configuration file not found.";
    } else {
        $config = Toml::ParseFile($files[0]);
    }
    return $config;
}

function generate_version_name(string $old_version)
{
    $arr = explode(".", $old_version);
    $current_date = date('Ymd');
    $version = "$current_date.1";
    
    if (count($arr) != 3 || $arr[0] != "release") {
        $version = "$current_date.1";

    } else {
        $old_date = $arr[1];
        $sequence = $arr[2];
        
        if ($current_date != $old_date) {
            $version = "$current_date.1";

        } else {
            $sequence = (int)$sequence + 1;
            $version = "$current_date.$sequence";
        }    
    }
    return $version;
}


\Deployer\set('artifact_file', mandatory(getenv('ARTIFACT_FILE'), '`ARTIFACT_FILE` environment variable'));
\Deployer\set('deploy_path', mandatory(getenv('DEPLOY_PATH'), '`DEPLOY_PATH` environment variable'));
\Deployer\set('dotenv_file', getenv('DOTENV_FILE'));
\Deployer\set('staging_path', \Deployer\get('deploy_path') . '/' . 'staging');
\Deployer\set('current_path', \Deployer\get('deploy_path') . '/' . 'current');