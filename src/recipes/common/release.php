<?php

namespace Deployer;

task('deploy:release:load_config', function () {
    
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


task('deploy:release:after', function () {
    $config = get('config');

    if (isset($config['command_hooks']['post_release'])) {
        run($config['command_hooks']['post_release']);
    }
});


task('deploy:release', function () {

    // Check if deploy path is a directory
    writeln("Checking deploy path.");
    $deploy_path = get('deploy_path');
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        error("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory");
    }

    // Create or read deploy path
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

    // Unzipping artifact to release
    writeln("Extracting artifact {{artifact_file}} to release {{release_path}}");
    run("unzip -qq {{artifact_file}} -d {{release_path}}");

    invoke('deploy:release:load_config');
    invoke('deploy:release:after');
});
