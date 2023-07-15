<?php

namespace Flyer\Utils\ReleaseCleanup;

use Deployer as d;

function cleanup_old_release(array $release_dir_list, string $previous_release_dir, string $app_id, int $keep_releases = 0, bool $async_cleanup = false)
{
    // Delete previous releases
    foreach ($release_dir_list as $release_dir) {
        if ($release_dir == $previous_release_dir) {
            continue;
        }

        if (!d\test("[ -d $release_dir ]")) {
            continue;
        }

        // the last component of the release dir path should be the release name right?
        // ... right?
        $release_name = basename($release_dir);

        if ($async_cleanup == true) {
            $log_file = d\parse("/tmp/$app_id.$release_name.log");
            d\run("rm -rf $release_dir > $log_file 2>&1 &");
        } else {
            d\run("rm -rf $release_dir");
        }
    }
}