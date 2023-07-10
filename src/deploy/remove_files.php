<?php

namespace Deployer;

task('deploy:remove_files', function () {
    if (!has('config')) {
        return;
    }

    if (!has('release_path') || !is_dir(get('release_path'))) {
        throw error("Release directory didn't exist. Is application released yet?");
    }

    $config = get('config');
    if (isset($config['remove'])) {
        if (is_array($config['remove'])) {
            $remove_list = $config['remove'];
            foreach ($remove_list as $to_be_removed) {
                $full_path = parse("{{release_path}}") . "/$to_be_removed";
                if (test("[ -f $full_path ]") || test("[ -d $full_path ]")) {
                    run("rm -rf $full_path");
                }
            }
        } else {
            // we fail fast and we fail often
            // this is needed so users can get clear feedback quickly that their config does not work 
            throw error("`remove` config is set to non-array.");
        }
    }
})->desc('Remove files specified in the `remove` config option.');