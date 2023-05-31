<?php

namespace Deployer;

task('deploy:prepare', function() {
    
    // Read Env
    set('artifact_file', getenv('ARTIFACT_FILE'));
    set('deploy_path', getenv('DEPLOY_PATH'));

    // Set symlink path
    set('current_path', '{{deploy_path}}/current');

    // Get all releases
    $release_list = [];
    foreach (glob(get('deploy_path') . '/release.*') as $file) {
        array_push($release_list, basename($file));
    }
    set('release_list', $release_list);
});
