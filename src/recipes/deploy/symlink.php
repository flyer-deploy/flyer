<?php

namespace Deployer;

task('deploy:symlink', function () {
    if (!has('current_path')) {
        throw error("Current Path is not specified. Is application released yet?");
    }

    // Check if release_path is set
    if (!has('release_path') || !is_dir(get('release_path'))) {
        throw error("Release directory didn't exist. Is application released yet?");
    }

    run("ln -sfn {{release_path}} {{current_path}}");
});
