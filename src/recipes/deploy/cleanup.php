<?php

namespace Deployer;

task('deploy:cleanup', function () {
    $release_list = get('release_list');
    $release_name = get('release_name');

    $delete_queue = array_filter($release_list, fn($release) => $release !== $release_name);
    foreach ($delete_queue as $release) {
        if (get('async_cleanup') == 1) {
            run("log_file=/tmp/{{app_id}}.{{release_name}}.log");
            run("rm -rf {{deploy_path}}/$release > log_file &");
        } else {
            run("rm -rf {{deploy_path}}/$release");
        }
    }
});