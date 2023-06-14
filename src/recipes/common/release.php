<?php

namespace Deployer;

task('deploy:release:preparation', function() {
    // Check if deploy path is a directory
    writeln("Checking deploy path.");
    $deploy_path = get('deploy_path');
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        throw error("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory");
    }

    run("mkdir -p {{deploy_path}}");
    set('release_list', array_map('basename', glob(get('deploy_path') . '/release.*')));

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

    set('release', $new_release);
    set('release_path', '{{deploy_path}}/{{release}}');
    run("mkdir -p {{release_path}}");

    // Assign chown to release path
    if (get('app_user') !== false && get('app_group') !== false) {
        writeln("Running chown {{app_user}}:{{app_group}} {{release_path}}");
        run("chown {{app_user}}:{{app_group}} {{release_path}}");

    } elseif (get('app_user') !== false) {
        writeln("Running chown {{app_user}} {{release_path}}");
        run("chown {{app_user}} {{release_path}}");

    } elseif (get('app_group') !== false) {
        writeln("Running chown {{app_group}} {{release_path}}");
        run("chgrp {{app_group}} {{release_path}}");
    }

    // Assign chmod to deploy path
    run("chmod u+rwx,g+rx  {{release_path}}");

    if (get('with_secure_default_permission') == 1) {
        run("setfacl -d -m g::r-- {{release_path}}");
    }
});


task('deploy:release:unzip_artifact', function () {
    run("mkdir -p {{release_path}}");

    if (get('app_group') !== false) {
        run("chmod g+s {{release_dir}}");
    }

    if (get('with_secure_default_permission') === 1) {
        run("setfacl -d -m g::r-- {{release_path}}");
    }

    writeln("Extracting artifact {{artifact_file}} to release {{release_path}}");
    run("unzip -qq {{artifact_file}} -d {{release_path}}");
});

task('deploy:release:load_config', function() {

    // Load yaml file from release
    $file = get('release_path') . '/flyer.yaml';
    if (file_exists($file)) {
        writeln("Config file flyer.yaml loaded.");
        $config = yaml_parse_file($file);
    } else {
        writeln("Configuration file not found.");
        $config = [];
    }

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

    set('config', $config);
});

task('deploy:release:after', function() {
    $config = get('config');

    if (isset($config['command_hooks']['post_release'])) {
        run($config['command_hooks']['post_release']);
    }
});


task('deploy:release', function() {
    invoke('deploy:release:preparation');
    invoke('deploy:release:unzip_artifact');
    invoke('deploy:release:load_config');
    invoke('deploy:release:after');
});
