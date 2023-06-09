<?php

namespace Deployer;

task('deploy:permission:user_group', function () {
    if (!isset(get('config')['permission']['user']) || isset(get('config')['permission']['group'])) {
        return;
    }

    $permission = get('config')['permission'];
    $user = $permission['user'];
    $group = $permission['group'];

    writeln("Assigning user and group to folder {{new_release_path}}");
    run("chown -R $user:$group {{new_release_path}}");
});

task('deploy:permission:writable_path', function () {
    if (isset(get('config')['permission']['writable_paths'])) {
        $writable_paths = get('config')['permission']['writable_paths'];

    } elseif (isset(get('config')['permission']['writable_paths'])) {
        $writable_paths = get('config')['permission']['writable_paths'];

    } else {
        return;
    }

    foreach ($writable_paths as $writable_path) {
        $path = get('new_release_path') . '/' .$writable_path['path'];

        $class = '';
        switch ($writable_path['by']) {
            case 'user':
                $class = 'u';
                break;
            case 'group':
                $class = 'g';
                break;
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

task('deploy:permission:acl', function() {
    echo "acl stuff \n";
});

task('deploy:permission', function() {
    invoke('deploy:permission:user_group');
    invoke('deploy:permission:writable_path');
    invoke('deploy:permission:acl');
});