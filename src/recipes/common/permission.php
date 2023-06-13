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

    $writable_mode = get('WRITABLE_MODE');

    foreach ($writable_paths as $writable_path) {
        $path = get('new_release_path') . '/' .$writable_path['path'];

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

        $recursive = '';
        if (isset($writable_path['recursive'])) {
            if ($writable_path['recursive'] === true) {
                $recursive = '-R';
            }
        }

        writeln("Creating writable path $path by $class");
        run("chmod $recursive $class+w $path");
    }
});

task('deploy:permission', function() {
    invoke('deploy:permission:writable_path');
});
