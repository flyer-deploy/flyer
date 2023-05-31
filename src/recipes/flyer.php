<?php
namespace Deployer;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../common/utils.php';
require __DIR__ . '/prepare.php';
require __DIR__ . '/release.php';
require __DIR__ . '/command_hook.php';
require __DIR__ . '/cleanup.php';

localhost();

task('deploy:load_config', function() {
    $config = get_config(get('new_release_path') . "/artifact");

    // Store config for future use
    set('config', $config);
});

task('deploy:symlink', function() {
    run("ln -sfn {{new_release_path}} {{current_path}}");
});

task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:load_config',
    'command_hook:post_deploy',

    'command_hook:pre_symlink',
    'deploy:symlink',
    'command_hook:post_symlink',

    'command_hook:start',
    'deploy:cleanup',
]);