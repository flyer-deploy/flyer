<?php

namespace Deployer;

task('deploy:permission:writable_path', function () {
    $writable_paths = get('config')['permission']['writable_paths'];

    foreach ($writable_paths as $writable_path) {
        $path = get('release_path') . '/' .$writable_path['path'];
        $recursive = '';

        if (isset($writable_path['recursive']) && $writable_path['recursive'] === true) {
            $recursive = '-R';
        }

        $class = '';
        switch ($writable_path['by']) {
            case 'user':
                $class = 'u';
                break;
            case 'group':
                $class = 'g';
                break;
            default:
                $class = 'ug';
                break;
        }   

        $recursive = '';

        writeln("Creating writable path $path by $class");
        run("chmod $recursive $class+w $path");
    }
});

task('deploy:permission:acl', function() {
    echo "acl stuff \n";
});

task('deploy:permission', function() {
    if (isset(get('config')['permission']['writable_paths'])) {
        invoke('deploy:permission:writable_path');
    }
    
    if (isset(get('config')['permission']['acl_list'])) {
        invoke('deploy:permission:acl');
    }
});