<?php

namespace Deployer;

task('deploy:additional', function () {
    if (!has('config')) {
        return;
    }

    $config = get('config');

    if (!isset($config['additional']['files'])) {
        return;
    }

    if (get('additional_files_dir') === false) {
        throw error("ADDITIONAL_FILES_DIR is not specified while flyer.yaml specifies `additional.files`.");
    }

    foreach($config['additional']['files'] as $file) {
        writeln("Copying file {{additional_files_dir}}/$file to {{release_path}}/$file");
        run("cp {{additional_files_dir}}/$file {{release_path}}/$file");
    }
});
