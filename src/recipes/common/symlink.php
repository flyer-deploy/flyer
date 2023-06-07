<?php

namespace Deployer;

task('deploy:symlink:before', function () {
    $config = get('config');

    if (isset($config['command_hooks']['pre_symlink'])) {
        run($config['command_hooks']['pre_symlink']);
    }
});


task('deploy:symlink:linking', function() {
    run("ln -sfn {{new_release_path}} {{current_path}}");
});


task('deploy:symlink:after', function () {
    $config = get('config');

    if (isset($config['command_hooks']['post_symlink'])) {
        run($config['command_hooks']['post_symlink']);
    }
});

task('deploy:symlink', function () {
    invoke('deploy:symlink:before');
    invoke('deploy:symlink:linking');
    invoke('deploy:symlink:after');
});

