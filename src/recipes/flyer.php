<?php

namespace Deployer;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../common/utils.php';

require __DIR__ . '/common/permission.php';
require __DIR__ . '/common/symlink.php';
require __DIR__ . '/common/release.php';
require __DIR__ . '/common/shared_dir.php';

localhost();


task('deploy:start', function () {
    $config = get('config');

    if (isset($config['command_hooks']['start'])) {
        run($config['command_hooks']['start']);
    }
});


task('deploy:prepare', function () {
    set('project_name', getenv('PROJECT_NAME'));
    set('repo_name', getenv('REPO_NAME'));
    set('shared_dir', getenv('SHARED_DIR'));
    set('artifact_file', getenv('ARTIFACT_FILE'));
    set('deploy_path', getenv('DEPLOY_PATH'));

    set('current_path', '{{deploy_path}}/current');
    set('release_list', array_map('basename', glob(get('deploy_path') . '/release.*')));
});


task('deploy', function () {
    invoke('deploy:prepare');
    invoke('deploy:release');
    invoke('deploy:permission');
    invoke('deploy:shared_dir');
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
