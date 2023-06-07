<?php

namespace Deployer;

task('deploy:shared_dir', function () {
    if (!get('shared_dir')) {
        return;
    }

    $project_name = get('project_name');
    $repo_name = get('repo_name');

    foreach(get('shared_dir') as $dir) {
        mkdir_if_not_exists("/var/share/$project_name/$repo_name/$dir");

        writeln("Moving shared dir content.");
        run("mv -f {{new_release_path}}/$dir /var/share/$project_name/$repo_name/$dir");
    
        writeln("Creating shared dir symlink.");
        run("ln -sfn /var/share/$project_name/$repo_name/$dir {{new_release_path}}/$dir");
    }
});