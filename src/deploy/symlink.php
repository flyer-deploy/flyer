<?php

namespace Deployer;

task('deploy:symlink', function () {
    depends([
        'current_path',
        'release_path',
    ]);

    run("ln -sfn {{release_path}} {{current_path}}");
});
