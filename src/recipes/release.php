<?php

namespace Deployer;

task('deploy:release', function() {

    foreach(get('release_list') as $release) {
        $current_date = date('Ymd');

        $arr      = explode(".", $release);
        $date     = $arr[1];
        $sequence = $arr[2];

        if ($current_date == $date) {
            $sequence = $sequence + 1;
            $new_release = "/release.$current_date.$sequence";
        }
    }

    // Unzip artifact
    writeln("Extracting artifact {{artifact_file}} to release {{deploy_path}}" . $new_release);
    run("unzip -qq {{artifact_file}} -d {{deploy_path}}" . $new_release);

    // Store new_release for future use
    set('new_release', $new_release);
    set('new_release_path', '{{deploy_path}}/{{new_release}}');
});