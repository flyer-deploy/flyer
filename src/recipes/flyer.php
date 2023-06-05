<?php

namespace Deployer;

require __DIR__ . '/../../vendor/autoload.php';

use Yosymfony\Toml\Toml;

localhost();

task('deploy:create_release', function () {
    $release_list = get('release_list');
    $current_date = date('Ymd');
    $new_release  = "/release.$current_date.1";

    foreach ($release_list as $release) {
        $arr      = explode(".", $release);
        $date     = $arr[1];
        $sequence = $arr[2];

        if ($current_date == $date) {
            $sequence++;
            $new_release = "/release.$current_date.$sequence";
        }
    }

    $deploy_path = get('deploy_path');
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        throw new ConfigurationException("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory");
    }
    run("mkdir -p {{deploy_path}}");

    // Unzip artifact
    writeln("Extracting artifact {{artifact_file}} to release {{deploy_path}}" . $new_release);
    run("unzip -qq {{artifact_file}} -d {{deploy_path}}" . $new_release);

    set('new_release', $new_release);
    set('new_release_path', '{{deploy_path}}/{{new_release}}');
});

task('deploy:load_config', function () {
    $file = get('new_release_path') . '/artifact/flyer.toml';

    if (file_exists($file)) {
        $config = Toml::ParseFile($file);
    } else {
        echo "Configuration file not found.";
        $config = [];
    }

    set('config', $config);
});

task('deploy:symlink', function () {
    run("ln -sfn {{new_release_path}} {{current_path}}");
});

task('cleanup', function () {
    $release_list = get('release_list');
    $new_release_path = get('new_release_path');

    $delete_queue = array_filter($release_list, fn ($release) => $release !== $new_release_path);

    foreach ($delete_queue as $release) {
        run("rm -rf {{deploy_path}}/$release");
    }
});


task('deploy', function () {
    set('artifact_file', getenv('ARTIFACT_FILE'));
    set('deploy_path', getenv('DEPLOY_PATH'));
    set('current_path', '{{deploy_path}}/current');
    set('release_list', array_map('basename', glob(get('deploy_path') . '/release.*')));

    invoke('deploy:create_release');
    invoke('deploy:load_config');

    $config = get('config');

    if (isset($config['template']['name'])) {
        $schema = $config['template']['name'];
        $path = __DIR__ . '/' . str_replace('.', '/', $schema) . '.php';

        if (file_exists($path)) {
            require $path;
        }
    }

    invoke('deploy:post_release');
    invoke('deploy:pre_symlink');
    invoke('deploy:symlink');
    invoke('deploy:post_symlink');

    invoke('cleanup');
});

task('deploy:post_release', function () {
    if (isset($config['command_hooks']['post_release'])) {
        run($config['command_hooks']['post_release']);
    }
});

task('deploy:pre_symlink', function () {
    if (isset($config['command_hooks']['pre_symlink'])) {
        run($config['command_hooks']['pre_symlink']);
    }
});

task('deploy:post_symlink', function () {
    if (isset($config['command_hooks']['post_symlink'])) {
        run($config['command_hooks']['post_symlink']);
    }
});

task('deploy:start', function () {
    if (isset($config['command_hooks']['start'])) {
        run($config['command_hooks']['start']);
    }
});
