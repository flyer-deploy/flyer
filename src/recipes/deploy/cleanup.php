<?php

namespace Deployer;

use React\EventLoop\Factory;
use React\Promise\Deferred;

task('deploy:cleanup', function () {
    $release_list = get('release_list');
    $release_name = get('release_name');

    $delete_queue = array_filter($release_list, fn ($release) => $release !== $release_name);
    foreach ($delete_queue as $release) {
        if (get('async_cleanup') == 1) {
            run("log_file=/tmp/{{app_id}}.{{release_name}}.log");
            run("rm -rf {{deploy_path}}/$release > log_file &");
        } else {
            run("rm -rf {{deploy_path}}/$release");
        }
    }

    // $loop = Factory::create();

    // $functions = array_map(function($release) {
    //     return function() use ($release) {
    //         run("rm -rf {{deploy_path}}/$release");
    //     };
    // }, $delete_queue);

    // foreach ($functions as $function) {
    //     $loop->addTimer(0, function () use ($function) {
    //         // Execute the function asynchronously
    //         $function();
    //     });
    // }
    
    // $loop->run(); // Start the event loop
});
