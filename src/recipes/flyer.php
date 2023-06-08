<?php

namespace Deployer;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../common/utils.php';

require __DIR__ . '/common/permission.php';
require __DIR__ . '/common/symlink.php';
require __DIR__ . '/common/release.php';
require __DIR__ . '/common/shared.php';

localhost();


task('deploy:start', function () {
    $config = get('config');

    if (isset($config['command_hooks']['start'])) {
        run($config['command_hooks']['start']);
    }
});


task('deploy:prepare', function () {
    set('app_id', getenv('APP_ID'));
    set('artifact_file', getenv('ARTIFACT_FILE'));
    set('deploy_path', getenv('DEPLOY_PATH'));
    set('shared_path', getenv('SHARED_PATH'));
    set('additional_files_dir', getenv('ADDITIONAL_FILES_DIR'));

    set('current_path', '{{deploy_path}}/current');
});


task('deploy:additional', function() {
    $config = get('config');
    
    if (!isset($config['additional']['files'])) {
        if (get('additional_files_dir') === false) {
            throw new ConfigurationException("ADDITIONAL_FILES_DIR is not specified while the configuration flyer.yaml did.");
        }

        foreach($config['additional']['files'] as $file) {
            writeln("Copying file {{additional_files_dir}}/$file to {{release_path}}/$file");
            run("cp {{additional_files_dir}}/$file {{release_path}}/$file");
        }
    }
});

task('deploy', function () {
    invoke('deploy:prepare');
    invoke('deploy:release');
    invoke('deploy:permission');
    invoke('additional');
    invoke('deploy:shared'); 
    invoke('deploy:symlink');
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
