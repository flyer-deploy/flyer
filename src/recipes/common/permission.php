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

task('deploy:permission:writeable_path', function () {
    if (isset(get('config')['permission']['writeable_paths'])) {
        $writeable_paths = get('config')['permission']['writeable_paths'];

    } elseif (isset(get('config')['permission']['writable_paths'])) {
        $writeable_paths = get('config')['permission']['writable_paths'];

    } else {
        return;
    }

    foreach ($writeable_paths as $writeable_path) {
        $path = get('new_release_path') . '/' .$writeable_path['path'];

        $class = '';
        switch ($writeable_path['by']) {
            case 'user':
                $class = 'u';
                break;
            case 'group':
                $class = 'g';
                break;
        }

        $recursive = '';
        if (isset($writeable_path['recursive'])) {
            if ($writeable_path['recursive'] === true) {
                $recursive = '-R';
            }
        }

        writeln("Creating writeable path $path by $class");
        run("chmod $recursive $class+w $path");
    }
});

task('deploy:permission:acl', function() {
    echo "acl stuff \n";
});

task('deploy:permission', function() {
    invoke('deploy:permission:user_group');
    invoke('deploy:permission:writeable_path');
    invoke('deploy:permission:acl');
});