<?php

namespace Deployer;

function route_command_hook(string $name)
{
    $config = get('config');

    if (isset($config['template']['name'])) {
        $schema = $config['template']['name'];
        $path = __DIR__ . '/' . str_replace('.', '/', $schema) . '.php';
        $command = str_replace('.', ':', $schema) . ':' . $name;
        
        if (file_exists($path)) {
            invoke($command);
        }
        
    } elseif (isset($config['command_hook'][$name])) {
        run($config['command_hook'][$name]);
    }
}

task('command_hook:post_release', function() {
    route_command_hook('post_release');
});

task('command_hook:pre_symlink', function() {
    route_command_hook('pre_symlink');
});

task('command_hook:post_symlink', function() {
    route_command_hook('post_symlink');
});

task('command_hook:start', function() {
    route_command_hook('start');
});