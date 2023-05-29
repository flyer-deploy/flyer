<?php

namespace Deployer;

require __DIR__ . '/../../../vendor/autoload.php';

/**
 * Zipped artifact will contain... the artifact directory. Directory may have flyer.toml or flyer.php file to configure the deployment.
 * */
task('deploy:prepare', function () {

})->desc(<<<EOF
deploy:prepare:
    1. Zipped artifac
    Extract zipped artifact to a destined `{{deploy_path}}`
    2. 
EOF);
