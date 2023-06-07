<?php

namespace Deployer;

task('deploy:release:check_deploy_path', function () {
    $release_list = get('release_list');
    $current_date = date('Ymd');
    $new_release  = "release.$current_date.1";

    // Preparation to sort release
    $sorted_release = [];
    foreach ($release_list as $release) {
        $arr      = explode(".", $release);
        $date     = $arr[1];
        $sequence = $arr[2];

        if ($current_date == $date) {
            $sorted_release[$sequence] = $release;
        }
    }

    // Sort the release
    ksort($sorted_release);
    end($sorted_release);

    // Get latest release sequence
    if (!empty($sorted_release)) {
        $last_sequence = key($sorted_release);
        $sequence = $last_sequence + 1;

        $new_release = "release.$current_date.$sequence";
    }

    // Check if deploy path is a directory
    $deploy_path = get('deploy_path');
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        throw new ConfigurationException("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory");
    }
    run("mkdir -p {{deploy_path}}");

    set('new_release', $new_release);
    set('new_release_path', '{{deploy_path}}/{{new_release}}');
});


task('deploy:release:unzip_artifact', function () {
    writeln("Extracting artifact {{artifact_file}} to release {{new_release_path}}");
    run("unzip -qq {{artifact_file}} -d {{new_release_path}}");
});


task('deploy:release:load_config', function () {
    $file = get('new_release_path') . '/flyer.yaml';

    if (file_exists($file)) {
        $config = yaml_parse_file($file);
    } else {
        writeln("Configuration file not found.");
        $config = [];
    }

    if (isset($config['template']['name'])) {
        $schema = $config['template']['name'];
        $path = __DIR__ . '/../' . str_replace('.', '/', $schema) . '.php';
    
        if (file_exists($path)) {
            require $path;
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
    invoke('deploy:release:check_deploy_path');
    invoke('deploy:release:unzip_artifact');
    invoke('deploy:release:load_config');
    invoke('deploy:release:after');
});
