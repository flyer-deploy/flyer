<?php

namespace Deployer;

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

function get_config(string $dir)
{
    $files  = glob($dir . '/flyer.toml');
    $config = null;

    if (empty($files)) {
        echo "Configuration file not found.";
    } else {
        $config = Toml::ParseFile($files[0]);
    }
    return $config;
}
