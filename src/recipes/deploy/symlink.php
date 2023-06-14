<?php

namespace Deployer;

task('deploy:symlink:before', function () {
    $config = get('config');

    if (isset($config['command_hooks']['pre_symlink'])) {
        run($config['command_hooks']['pre_symlink']);
    }
});


task('deploy:symlink:after', function () {
    $config = get('config');

    if (isset($config['command_hooks']['post_symlink'])) {
        run($config['command_hooks']['post_symlink']);
    }
});


task('deploy:symlink', function () {
    $config = get('config');
    
    if ($config['command_hooks']['pre_symlink'] != "null") {
        invoke('deploy:symlink:before');
    }
    
    // Symlink release to current
    writeln("Creating symbolic link {{release_path}} to {{current_path}}");
    run("ln -sfn {{release_path}} {{current_path}}");

    if ($config['command_hooks']['post_symlink'] != "null") {
        invoke('deploy:symlink:after');
    }
});

