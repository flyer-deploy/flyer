<?php

namespace Deployer;

task('deploy:dependencies', function () {
    if (get('dependencies') == null) {
        writeln("Dependencies config not found. Skipping.");
        return;
    }

    $dependencies = get('dependencies');
    if (!is_array($dependencies)) {
        throw error('Config \`dependencies\` is not of type array.');
    }

    foreach ($dependencies as $cmd) {
        if (!commandExist($cmd)) {
            throw error("\`$cmd\` command is not available.");
        }
    }
});