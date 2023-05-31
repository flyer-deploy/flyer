<?php

namespace Deployer;

function route_command_hook(string $name) 
{
    $config = get('config');

    if (isset($config['template']['name'])) {
        $schema = $config['template']['name'];
        $path = '/' . str_replace('.', '/', $schema) . '.php';

        if (file_exists(__DIR__ . $path)) {
            require_once __DIR__ . $path;
            command_hook($name);

        } else {
            writeln("Invalid template name $schema");
        }

    } elseif (isset($config['command_hook'][$name])) {
        run($config['command_hook'][$name]);
    }
}

task('command_hook:post_deploy', function() {
    route_command_hook('post_deploy');
});

task('command_hook:pre_symlink', function() {
    route_command_hook('post_deploy');
});

task('command_hook:post_symlink', function() {
    route_command_hook('post_deploy');
});

task('command_hook:start', function() {
    route_command_hook('post_deploy');
});