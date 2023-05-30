<?php

namespace TheRecipes\Flyer;

require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../common/utils.php';

/**
 * Zipped artifact will contain... the artifact directory. Directory may have flyer.toml or flyer.php file to configure the deployment.
 * */

\Deployer\localhost();

\Deployer\task('deploy:prepare', function () {
    $artifact_file = \Deployer\get('artifact_file');
    $deploy_path   = \Deployer\get('deploy_path');
    $current_path  = \Deployer\get('current_path');
    $staging_path  = \Deployer\get('staging_path');

    $files       = glob($deploy_path . '/release.*');
    $old_version = array_slice($files, -1)[0];
    $new_version = "/release." . \TheRecipes\generate_version_name($old_version);

    $deploy_path_final = $deploy_path . $new_version;

    \TheRecipes\mkdir_if_not_exists($deploy_path_final);

    \Deployer\writeln("Extracting archived artifact $artifact_file to staging $deploy_path_final");
    \Deployer\run("unzip -qq $artifact_file -d $deploy_path_final");
    \Deployer\run("ln -sfn $artifact_file $current_path");
});

\Deployer\task('deploy',[
    'deploy:prepare'
]);
