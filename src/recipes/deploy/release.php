<?php

namespace Deployer;

task('deploy:release:preparation', function () {
    $deploy_path = get('deploy_path');

    // Check if deploy path is a directory
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        throw(error("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory"));
    }

    // Create deploy path
    run("mkdir -p {{deploy_path}}/releases");

    // Assign owner to deploy path
    if (get('app_user') !== false && get('app_group') !== false) {
        run("chown {{app_user}}:{{app_group}} {{deploy_path}}");

    } elseif (get('app_user') !== false) {
        run("chown {{app_user}} {{deploy_path}}");

    } elseif (get('app_group') !== false) {
        run("chgrp {{app_group}} {{deploy_path}}");
    }

    // Assign chmod to deploy path
    run("chmod u+rwx,g+rx  {{deploy_path}}");

    // Get all releases
    set('releases_list', array_map('basename', glob($deploy_path . '/release.*')));

    // Get release name
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
});

task('deploy:release:unzip_artifact', function () {
    // Create release path
    run("mkdir -p {{release_path}}");

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

task('deploy:release:load_config', function () {
    // Load yaml file from release
    set('config', yaml_parse_file(get('release_path') . '/flyer.yaml') ?? []);

    // Load template if specified
    if (isset($config['template']['name'])) {
        $schema = $config['template']['name'];
        $path = __DIR__ . '/../' . str_replace('.', '/', $schema) . '.php';

        if (file_exists($path)) {
            require $path;
            writeln("Using template $schema");
        } else {
            writeln("Template name $schema invalid");
        }
    }
});

task('deploy:release:after', function () {
    $config = get('config');

    if (isset($config['command_hooks']['post_release'])) {
        run($config['command_hooks']['post_release']);
    }
});

task('deploy:release', function () {
    invoke('deploy:release:preparation');
    invoke('deploy:release:unzip_artifact');
    invoke('deploy:release:load_config');

    $config = get('config');
    if ($config['command_hooks']['post_release'] != "null") {
        invoke('deploy:release:after');
    }
});
