<?php

namespace Deployer;

task('deploy:dependencies', function () {
    if (!isset(get('config')['dependencies'])) {
        return;
    }

    $dependencies = get('config')['dependencies'];
    foreach ($dependencies as $cmd) {
        if (!commandExist($cmd)) {
            throw error("$cmd is not installed yet.");
        }
    }
});