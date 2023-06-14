<?php

namespace Deployer;

task('deploy:permission:writable_path', function() {
    $writable_paths = get('config')['permission']['writable_paths'];


    foreach ($writable_paths as $writable_path) {
        $path = get('new_release_path') . '/' .$writable_path['path'];

        $writable_mode = get('writable_mode');

        $class = '';
        switch ($writable_mode) {
            case 'by_user':
                $class = 'u';
                break;
            case 'by_group':
                $class = 'g';
                break;
            default:
                $class = 'ug';
                break;
        }
        writeln("Creating writable path $path by $class");

        $recursive = isset($writable_path['recursive']) ? !!$writable_path['recursive'] : false;
        $maxdepth = $recursive === false ? '-maxdepth 1' : '';
        run("find $path $maxdepth -type f -exec chmod $class+w {} \;");
        run("find $path $maxdepth -type d -exec chmod $class+wx {} \;");
    }
});

task('deploy:permission', function() {
    if (isset(get('config')['permission']['writable_paths'])) {
        invoke('deploy:permission:writable_path');
    }

    if (isset(get('config')['permission']['acl_list'])) {
        invoke('deploy:permission:acl');
    }
});
