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
    run("test -d $dir || mkdir $dir");
}
