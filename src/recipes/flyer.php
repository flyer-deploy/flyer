<?php

namespace Deployer;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../common/utils.php';

use Yosymfony\Toml\Toml;

localhost();

// Command Hooks
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

// Common Recipe
task('deploy:create_release', function () {
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

    // Unzip artifact
    writeln("Extracting artifact {{artifact_file}} to release {{deploy_path}}/" . $new_release);
    run("unzip -qq {{artifact_file}} -d {{deploy_path}}/" . $new_release);

    set('new_release', $new_release);
    set('new_release_path', '{{deploy_path}}/{{new_release}}');
});

task('deploy:load_config', function () {
    echo get('new_release_path');
    $file = get('new_release_path') . '/flyer.yaml';

    if (file_exists($file)) {
        $config = yaml_parse_file($file);
    } else {
        writeln("Configuration file not found.");
        $config = [];
    }

    set('config', $config);
});

task('deploy:set_permission', function () {
    if (!isset(get('config')['permission']['user']) || isset(get('config')['permission']['group'])) {
        return;
    }

    $permission = get('config')['permission'];
    $user = $permission['user'];
    $group = $permission['group'];

    writeln("Assigning user and group to folder {{new_release_path}}");
    run("chown -R $user:$group {{new_release_path}}");
});

task('deploy:set_writeable_path', function () {
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

task('deploy:symlink', function () {
    run("ln -sfn {{new_release_path}} {{current_path}}");
});

task('deploy:shared_dir', function () {
    if (!get('shared_dir')) {
        return;
    }

    $project_name = get('project_name');
    $repo_name = get('repo_name');

    mkdir_if_not_exists("/var/share/$project_name/$repo_name");

    writeln("Moving shared dir content.");
    run("mv -f {{shared_dir}} /var/share/$project_name/$repo_name");

    writeln("Creating shared dir symlink.");
    run("ln -sfn /var/share/$project_name/$repo_name {{shared_dir}}");
});

task('deploy', function () {
    set('project_name', getenv('PROJECT_NAME'));
    set('repo_name', getenv('REPO_NAME'));
    set('shared_dir', getenv('SHARED_DIR'));
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

    invoke('deploy:set_permission');
    invoke('deploy:set_writeable_path');
    invoke('deploy:post_release');

    invoke('deploy:pre_symlink');
    invoke('deploy:shared_dir');
    invoke('deploy:symlink');
    invoke('deploy:post_symlink');

    invoke('cleanup');
});

task('cleanup', function () {
    $release_list = get('release_list');
    $new_release = get('new_release');

    $delete_queue = array_filter($release_list, fn ($release) => $release !== $new_release);

    foreach ($delete_queue as $release) {
        run("rm -rf {{deploy_path}}/$release");
    }
});
