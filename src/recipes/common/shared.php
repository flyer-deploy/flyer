<?php

namespace Deployer;

task('deploy:shared:dirs', function() {
    $shared_path = get('shared_path');
    $config = get('config');

    foreach ($config['shared']['dirs'] as $a) {
        foreach ($config['shared']['dirs'] as $b) {
            if ($a !== $b && strpos(rtrim($a, '/') . '/', rtrim($b, '/') . '/') === 0) {
                throw new Exception("Can not share same dirs `$a` and `$b`.");
            }
        }
    }

    foreach ($config['shared']['dirs'] as $dir) {
        $dir = trim($dir, '/');

        if (!test("[ -d $shared_path/$dir ]")) {
            run("mkdir -p $shared_path/$dir");
            if (test("[ -d $(echo {{release_path}}/$dir) ]")) {
                run("cp -r {{release_path}}/$dir $shared_path/" . dirname($dir));
            }
        }

        // Remove from source.
        run("rm -rf {{release_path}}/$dir");

        // Create path to shared dir in release dir if it does not exist.
        // Symlink will not create the path and will fail otherwise.
        run("mkdir -p `dirname {{release_path}}/$dir`");

        // Symlink shared dir to release dir
        run("ln -sfn $shared_path/$dir {{release_path}}/$dir");
    }
});


task('deploy:shared:files', function() {
    $shared_path = get('shared_path');
    $config = get('config');

    foreach ($config['shared']['files'] as $file) {
        $dirname = dirname(parse($file));

        // Create dir of shared file if not existing
        if (!test("[ -d $shared_path/$dirname ]")) {
            run("mkdir -p $shared_path/$dirname");
        }

        // Check if shared file does not exist in shared.
        // and file exist in release
        if (!test("[ -f $shared_path/$file ]") && test("[ -f {{release_path}}/$file ]")) {
            // Copy file in shared dir if not present
            run("cp -r {{release_path}}/$file $shared_path/$file");
        }

        // Remove from source.
        run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");

        // Ensure dir is available in release
        run("if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi");

        // Touch shared
        run("[ -f $shared_path/$file ] || touch $shared_path/$file");

        // Symlink shared dir to release dir
        run("ln $shared_path/$file {{release_path}}/$file");
    }
});


task('deploy:shared', function () {
    $config = get('config');

    // Get shared path
    set('shared_path', function() {
        $shared_path = "/var/share/" . get('app_id');
        if (get('shared_path') != false) {
            $shared_path = get('shared_path');
        }
        return $shared_path;
    });

    if (isset($config['shared']['dirs'])) {
        invoke('deploy:shared:dirs');
    }

    if (isset($config['shared']['files'])) {
        invoke('deploy:shared:files');
    }
});
