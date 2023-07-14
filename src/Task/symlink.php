<?php

namespace Flyer\Task;

use function Flyer\Utils\Common\depends;

use function Deployer\task;
use function Deployer\run;

task('deploy:symlink', function () {
    depends([
        'current_path',
        'release_path',
    ]);

    run("ln -sfn {{release_path}} {{current_path}}");
});