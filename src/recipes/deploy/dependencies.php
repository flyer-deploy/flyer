<?php

namespace Deployer;

task('deploy:dependencies', function () {
    if (!isset(get('config')['dependencies'])) {
        return;
    }

    $dependencies = get('config')['dependencies'];
    if (!is_array($dependencies)) {
        throw new ConfigurationException('Config \`dependencies\` is not of type array.');
    }

    foreach ($dependencies as $cmd) {
        if (!commandExist($cmd)) {
            throw error("\`$cmd\` command is not available.");
        }
    }
});