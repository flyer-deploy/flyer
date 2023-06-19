<?php

namespace Deployer;

task('deploy:cleanup', function () {
    $release_list = get('release_list');
    $release_name = get('release_name');

    $delete_queue = array_filter($release_list, fn ($release) => $release !== $release_name);

    foreach ($delete_queue as $release) {
        run("rm -rf {{deploy_path}}/$release");
    }
});
