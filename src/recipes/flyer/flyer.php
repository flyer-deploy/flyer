<?php

namespace Deployer;

require __DIR__ . '/../../../vendor/autoload.php';

set('versioned_release', mandatory(getenv('VERSIONED_RELEASE')))
    ->desc('Whether to put each release version in its own directory');


/**
 * Zipped artifact will contain directories with this structure:
 * |- app1
 * |-
 *
 * */
task('deploy:prepare', function () {

})->desc(<<<EOF
deploy:prepare:
    1. Zipped artifac
    Extract zipped artifact to a destined `artifact_extract_path`
    2. 
EOF);
