<?php
namespace Deployer;

use Yosymfony\Toml\Toml;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../common/utils.php';
require __DIR__ . '/command_hook.php';
require __DIR__ . '/web/nginx.php';


localhost();

task('deploy:setup', function() {
    // Read Env
    set('artifact_file', getenv('ARTIFACT_FILE'));
    set('deploy_path', getenv('DEPLOY_PATH'));

    // Set symlink path
    set('current_path', '{{deploy_path}}/current');

    // Get all releases
    set('release_list', array_map('basename', glob(get('deploy_path') . '/release.*')));
});

task('deploy:release', function() {
    $release_list = get('release_list');
    $current_date = date('Ymd');
    $new_release  = "/release.$current_date.1";

    foreach($release_list as $release) {
        $arr      = explode(".", $release);
        $date     = $arr[1];
        $sequence = $arr[2];

        if ($current_date == $date) {
            $sequence++;
            $new_release = "/release.$current_date.$sequence";
        }
    }

    // Unzip artifact
    writeln("Extracting artifact {{artifact_file}} to release {{deploy_path}}" . $new_release);
    run("unzip -qq {{artifact_file}} -d {{deploy_path}}" . $new_release);

    set('new_release', $new_release);
    set('new_release_path', '{{deploy_path}}/{{new_release}}');
});

task('deploy:load_config', function() {
    $file = get('new_release_path') . '/artifact/flyer.toml';

    if (file_exists($file)) {
        $config = Toml::ParseFile($file);
    } else {
        echo "Configuration file not found.";
        $config = [];
    }

    set('config', $config);
});

task('deploy:symlink', function() {
    run("ln -sfn {{new_release_path}} {{current_path}}");
});

task('deploy:cleanup', function() {
    $release_list = get('release_list');
    $new_release_path = get('new_release_path');

    $delete_queue = array_filter($release_list, fn($release) => $release !== $new_release_path);
    
    foreach ($delete_queue as $release) {
        run("rm -rf {{deploy_path}}/$release");
    }
});


task('deploy:prepare', [
    'deploy:setup',
    'deploy:release',
    'deploy:load_config',
    'command_hook:post_release'
]);

task('deploy:publish', [
    'command_hook:pre_symlink',
    'deploy:symlink',
    'command_hook:post_symlink',
    'command_hook:start',
    'deploy:cleanup',
]);

task('deploy', [
    'deploy:prepare',
    'deploy:publish'
]);

