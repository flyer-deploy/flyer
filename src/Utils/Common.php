<?php

namespace Flyer\Utils\Common;

use function Deployer\writeln;
use function Deployer\run;
use function Deployer\get;
use function Deployer\error;

function mandatory($value, $key)
{
    if (empty($value)) {
        throw new \Deployer\Exception\ConfigurationException("Please specify $key");
    }
    return $value;
}

function mkdir_if_not_exists(string $dir)
{
    writeln("Creating dir $dir.");
    run("test -d $dir || mkdir -p $dir");
}

function depends(array $var_list)
{
    foreach ($var_list as $var) {
        $res = get($var);

        if ($res === null) {
            throw error("`$var` not specified.");
        }

        if ($res === false) {
            throw error("`$var` not specified.");
        }
    }
}

/**
 * Make life easier without specifying `isset()` when calling an array
 *
 * @param array $array
 * @param string ...$keys
 * @return mixed
 * @return null
 */
function obtain(array $array, ...$keys)
{
    $key = array_shift($keys);

    if (!isset($array[$key])) {
        return null;
    }

    $level = $array[$key];

    if (count($keys) === 0) {
        return $level;
    }

    return obtain($level, ...$keys);
}