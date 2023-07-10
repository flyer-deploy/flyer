<?php

namespace Deployer;

task('deploy:release', function () {
    $deploy_path = get('deploy_path');
    $current_path = get('current_path');
    $artifact_file = get('artifact_file');

    // Check if deploy path is a directory
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        throw error("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory");
    }

    // Create deploy directory if not exist
    run("mkdir -p {{deploy_path}}");

    // Get all releases
    set('release_list', array_map('basename', glob($deploy_path . '/release.*')));

    // Generate release name from previous releases
    set('release_name', function () {
        $release_list = get('release_list');
        $current_date = date('Ymd');
        $new_release  = "release.$current_date.1";

        if (!empty($release_list)) {
            natsort($release_list);
            [$_, $date, $sequence] = explode('.', end($release_list));
            if ($date === $current_date) {
                $sequence++;
                $new_release = "release.$current_date.$sequence";
            }
        }
        return $new_release;
    });

    // Set release path
    set('release_path', '{{deploy_path}}/{{release_name}}');

    // If current_path points to something like "/var/www/html", make sure it is
    // a symlink and not a directory.
    if (test('[ ! -L {{current_path}} ] && [ -d {{current_path}} ]')) {
        throw error("There is a directory (not symlink) at {{current_path}}.\n Remove this directory so it can be replaced with a symlink for atomic deployments.");
    }

    // Unzip artifact
    invoke('deploy:release:unzip_artifact');
});

task('deploy:release:unzip_artifact', function () {
    // Create release path
    run("mkdir -p {{release_path}}");

    // Assign owner to release path
    if (get('app_user') !== false && get('app_group') !== false) {
        run("chown {{app_user}}:{{app_group}} {{release_path}}");

    } elseif (get('app_user') !== false) {
        run("chown {{app_user}} {{release_path}}");

    } elseif (get('app_group') !== false) {
        run("chgrp {{app_group}} {{release_path}}");
    }

    // Assign chmod to release path
    run("chmod u+rwx,g+rx  {{release_path}}");

    // Chmod all the item to be added to this directory
    if (get('app_group') !== false) {
        run("chmod g+s {{release_path}}");
    }

    // Idk what this do
    if (get('with_secure_default_permission') === 1) {
        run("setfacl -d -m g::r-- {{release_path}}");
    }

    // Extract artifact to release
    run("unzip -qq {{artifact_file}} -d {{release_path}}");
});
