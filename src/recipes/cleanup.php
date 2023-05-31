<?php

namespace Deployer;

task('deploy:cleanup', function() {
    $release_list = get('release_list');
    $new_release_path = get('new_release_path');

    $delete_queue = array_filter($release_list, function($release) use ($new_release_path) {
        return $release !== $new_release_path;
    });
    
    foreach ($delete_queue as $release) {
        run("rm -rf {{deploy_path}}/$release");
    }
});
