<?php

namespace Flyer\Task;

use Flyer\Config;
use function Flyer\Utils\Common\depends;
use function Flyer\Utils\Common\obtain;

use function Deployer\task;
use function Deployer\run;
use function Deployer\get;
use function Deployer\writeln;
use function Deployer\set;
use function Deployer\has;

task('deploy:writable', function () {
    if (get('writable_paths') == null) {
        writeln("Writable config not found. Skipping.");
        return;
    }

    depends([
        'release_path',
    ]);

    // Set default writable mode
    if (get('writable_mode') === false || !has('writable_mode')) {
        set('writable_mode', 'by_group');
    }

    $release_path = get('release_path');

    /** @var Config\WritablePath[]  */
    $writable_paths = get('writable_paths');

    $mode = get('writable_mode');

    // Set writables
    foreach ($writable_paths as $writable) {
        $path = $release_path . '/' . $writable->path;

        $maxdepth = '';
        $maxdepth = $writable->recursive ? '' : '-maxdepth 1';

        $who = ($mode == 'by_user') ? 'u' : (($mode == 'by_group') ? 'g' : '');

        run("find -L $path $maxdepth -type f -exec chmod $who+w {} \;");
        run("find -L $path $maxdepth -type d -exec chmod $who+wx {} \;");

        $chown_identifier = '';
        if (!empty(get('app_user'))) {
            $chown_identifier .= get('app_user');
        }
        if (!empty(get('app_group'))) {
            $chown_identifier .= ':' . get('app_group');
        }
        if (!empty($chown_identifier)) {
            // `find -L chown` so symlinks user and/or group are correctly set
            run("find -L $path $maxdepth -type d -exec chown $chown_identifier {} \;");
        }

        if ($writable->files_default_writable === true) {
            // is this considered hack?
            run("find -L $path $maxdepth -type d -exec setfacl -d -m $who::rwX {} \;");
        }
    }
});