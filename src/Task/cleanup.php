<?php

namespace Flyer\Task;

use function Flyer\Utils\Common\depends;
use function Flyer\Utils\ReleaseCleanup\cleanup_old_release;
use function Flyer\Utils\Path\path_join;

use function Deployer\task;
use function Deployer\get;

task('deploy:cleanup', function () {
    depends([
        'app_id',
        'deploy_path',
        'release_list',
        'release_name',
    ]);

    $app_id = get('app_id');
    $async_cleanup = get('async_cleanup');

    $release_list = array_map(function ($release_dir) {
        return path_join(get('deploy_path'), $release_dir);
    }, get('release_list') ?? []);
    $previous_release_name = get('previous_release_name');

    cleanup_old_release($release_list, $previous_release_name, $app_id, 0, $async_cleanup);
});