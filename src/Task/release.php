<?php

namespace Flyer\Task;

use function Flyer\Utils\Common\depends;
use function Flyer\Utils\Common\obtain;

use function Deployer\task;
use function Deployer\get;
use function Deployer\set;
use function Deployer\run;
use function Deployer\error;
use function Deployer\test;

function generate_release_version(array $release_list)
{
    $current_date = date('Ymd');

    $version = "$current_date.1";

    if (!empty($release_list)) {
        natsort($release_list);
        [$_, $date, $sequence] = explode('.', end($release_list));
        if ($date === $current_date) {
            $sequence++;
            $version = "$current_date.$sequence";
        }
    }

    return $version;
}

function prepare_deploy_path()
{
    $deploy_path = get('deploy_path');

    // Check if deploy path is a directory
    if (file_exists($deploy_path) && !is_dir($deploy_path)) {
        throw error("Deploy path {{deploy_path}} is a regular file, not an existing or a non-existent directory");
    }

    run("mkdir -p {{deploy_path}}");

    // Check if current path is a valid symlink
    if (test("[ ! -L {{current_path}} ] && [ -d {{current_path}} ]")) {
        throw error("There is a directory (not symlink) at {{current_path}}.\n Remove this directory so it can be replaced with a symlink for atomic deployments.");
    }
}

function prepare_release_path()
{
    $app_user = get('app_user');
    $app_group = get('app_group');

    run("mkdir -p {{release_path}}");

    // Assign owner to release_path
    if ($app_user !== false && $app_group !== false) {
        run("chown {{app_user}}:{{app_group}} {{release_path}}");
    } elseif ($app_user !== false) {
        run("chown {{app_user}} {{release_path}}");
    } elseif ($app_group !== false) {
        run("chgrp {{app_group}} {{release_path}}");
    }

    // Set permission chmod
    run("chmod u+rwx,g+rx  {{release_path}}");
    if ($app_group !== false) {
        run("chmod g+s {{release_path}}");
    }

    // Set File Access List (setfacl)
    if (get('with_secure_default_permission') === 1) {
        run("setfacl -d -m g::r-- {{release_path}}");
    }
}

task('deploy:release', function () {
    depends([
        'deploy_path',
        'current_path',
        'artifact_file'
    ]);

    prepare_deploy_path();

    set('release_list', function () {
        $list = array_map('basename', glob(get('deploy_path') . "/release.*"));
        if ($list == null) {
            $list = [];
        }

        return $list;
    });

    $release_list = get('release_list');
    $release_version = generate_release_version($release_list);
    set('release_version', $release_version);

    // Generate release name from previous releases

    set('release_name', function () {
        $release_version = get('release_version');
        $new_release = "release.$release_version";

        return $new_release;
    });

    set('release_path', "{{deploy_path}}/{{release_name}}");

    set('previous_release_name', function () {
        $release_list = get('release_list');
        natsort($release_list);
        return end($release_list);
    });

    prepare_release_path();

    // Extract artifact to release path
    run("unzip -qq {{artifact_file}} -d {{release_path}}");
});