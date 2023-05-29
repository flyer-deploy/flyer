<?php

namespace Deployer;

function mandatory($value, $key)
{
    if (empty($value)) {
        throw new Exception\ConfigurationException("Please specify $key");
    }
    return $value;
}

function mkdir_if_not_exists(string $dir)
{
    run("test -d $dir || mkdir $dir");
}
