<?php

namespace Deployer;

task('deploy:remove', function () {
    if (get('remove') == null) {
        writeln("Remove config not found. Skipping.");
        return;
    }

    depends(['release_path']);

    $remove_list = get('remove');
    $release_path = get('release_path');

    if (!is_array($remove_list)) {
        throw error("`remove` config is set to non-array.");
    }

    foreach ($remove_list as $item) {
        $full_path = parse($release_path) . "/$item";
        if (test("[ -f $full_path ]") || test("[ -d $full_path ]")) {
            run("rm -rf $full_path");
        }
    }
});