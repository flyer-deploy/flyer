<?php

namespace Flyer\Task;

use function Flyer\Utils\Common\depends;

use function Deployer\task;
use function Deployer\get;
use function Deployer\writeln;
use function Deployer\run;

task('deploy:additional', function () {
    if (get('additional') == null) {
        writeln("Additional config not found. Skipping.");
        return;
    }

    depends([
        'additional_files_dir',
        'release_path',
    ]);

    $additional_files = get('additional');
    $additional_files_dir = get('additional_files_dir');
    if ($additional_files == null) {
        echo "KONTOLODON";
    }
    $release_path = get('release_path');

    foreach ($additional_files as $file) {
        writeln("Copying file $additional_files_dir/$file to $release_path/$file");
        run("cp $additional_files_dir/$file $release_path/$file");
    }
});