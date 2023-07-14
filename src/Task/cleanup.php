<?php

namespace Flyer\Task;

use function Flyer\Utils\Common\depends;

use function Deployer\task;
use function Deployer\get;
use function Deployer\run;
use function Deployer\test;
use function Deployer\parse;

task('deploy:cleanup', function () {
    depends([
        'app_id',
        'deploy_path',
        'release_list',
        'release_name',
    ]);

    $app_id = get('app_id');
    $async_cleanup = get('async_cleanup');

    $deploy_path = get('deploy_path');
    $release_list = get('release_list');
    $release_name = get('release_name');
    $previous_release_name = get('previous_release_name');

    // Delete previous releases
    foreach ($release_list as $release) {
        if ($release == $previous_release_name || $release == $release_name) {
            continue;
        }

        if (!test("[ -d $deploy_path/$release ]")) {
            continue;
        }

        if ($async_cleanup == true) {
            $log_file = parse("/tmp/$app_id.$release_name.log");
            run("rm -rf $deploy_path/$release > $log_file 2>&1 &");
        } else {
            run("rm -rf $deploy_path/$release");
        }
    }
});