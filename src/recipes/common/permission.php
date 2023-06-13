<?php

namespace Deployer;

task('deploy:permission:writable_path', function() {
    if (isset(get('config')['permission']['writable_paths'])) {
        $writable_paths = get('config')['permission']['writable_paths'];

    } elseif (isset(get('config')['permission']['writable_paths'])) {
        $writable_paths = get('config')['permission']['writable_paths'];

    } else {
        return;
    }

    $writable_mode = get('writable_mode');

    foreach ($writable_paths as $writable_path) {
        $path = get('release_path') . '/' .$writable_path['path'];

        $class = '';
        switch ($writable_mode) {
            case 'by_user':
                $class = 'u';
                break;
            case 'by_group':
                $class = 'g';
                break;
            default:
                throw error("Invalid writable_mode value: $writable_mode");
        }

        writeln("Creating writable path $path by $class");

        $recursive = isset($writable_path['recursive']) ? !!$writable_path['recursive'] : false;
        $maxdepth = $recursive === false ? '-maxdepth 1' : '';
        run("find $path $maxdepth -type f -exec chmod g+w {} \;");
        run("find $path $maxdepth -type d -exec chmod g+wx {} \;");
    }
});

task('deploy:permission', function() {
    invoke('deploy:permission:writable_path');
});
