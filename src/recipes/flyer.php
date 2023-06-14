<?php

namespace Deployer;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../common/utils.php';
require __DIR__ . '/deploy/permission.php';
require __DIR__ . '/deploy/symlink.php';
require __DIR__ . '/deploy/release.php';
require __DIR__ . '/deploy/shared.php';

localhost();


task('deploy:start', function () {
    $config = get('config');

    if (isset($config['command_hooks']['start'])) {
        run($config['command_hooks']['start']);
    }
});


task('deploy:prepare', function () {
    set('app_id', mandatory(getenv('APP_ID')));
    set('app_user', getenv('APP_USER'));
    set('app_group', getenv('APP_GROUP'));
    set('artifact_file', mandatory(getenv('ARTIFACT_FILE')));
    set('deploy_path', mandatory(getenv('DEPLOY_PATH')));
    set('shared_path', getenv('SHARED_PATH'));
    set('additional_files_dir', getenv('ADDITIONAL_FILES_DIR'));
    set('with_secure_default_permission', getenv('WITH_SECURE_DEFAULT_PERMISSIONS'));

    set('current_path', '{{deploy_path}}/current');

    if (get('with_secure_default_permission') === 1 && !commandExist('setfacl')) {
        writeln("YOU should be ashamed for not installing setfacl >:(");
    }
});


task('deploy:additional', function() {
    $config = get('config');
    
    if (isset($config['additional']['files'])) {
        if (get('additional_files_dir') === false) {
            throw(error("ADDITIONAL_FILES_DIR is not specified while the configuration flyer.yaml did."));
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
    invoke('deploy:additional');
    invoke('deploy:shared'); 
    invoke('deploy:symlink');
    invoke('deploy:start');
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
