<?php

namespace Deployer;

task('deploy:writable', function () {
    // There must be flyer.yaml configuration
    if (!has('config') || !isset(get('config')['permission']['writable_paths'])) {
        return;
    }

    // Check if release_path is set
    if (!has('release_path') || !is_dir(get('release_path'))) {
        throw error("Release directory didn't exist. Is application released yet?");
    }

    // Set default writable mode
    if (get('writable_mode') === false || !has('writable_mode')) {
        set('writable_mode', 'by_group');
    }

    $writable_paths = get('config')['permission']['writable_paths'];

    // Set writables
    foreach ($writable_paths as $writable) {
        $path = get('release_path') . '/' . $writable['path'];
        $mode = get('writable_mode');
        $recursive = isset($writable_path['recursive']) ? !!$writable['recursive'] : false;
        $maxdepth = $recursive ? '' : '-maxdepth 1';

        $who = '';
        switch ($mode) {
            case 'by_user':
                $who = 'u';
                break;

            case 'by_group':
                $who = 'g';
                break;
        }

        run("find -L $path $maxdepth -type f -exec chmod $who+w {} \;");
        run("find -L $path $maxdepth -type d -exec chmod $who+wx {} \;");

        $chown_identifier = '';
        if (!empty(get('app_user'))) {
            $chown_identifier .= get('app_user');
        }
        if (!empty(get('app_group'))) {
            $chown_identifier .= ':' . get('app_group');
        }
        if (!empty($chown_identifier)) {
            // `find -L chown` so symlinks user and/or group are correctly set
            run("find -L $path $maxdepth -exec chown $chown_identifier {} \;");
        }
    }
});