<?php

namespace Flyer\Task;

use function Deployer\task;
use function Deployer\get;
use function Deployer\set;
use function Deployer\error;
use function Deployer\warning;
use function Deployer\has;

use Symfony\Component\Yaml\Yaml;

task('deploy:load_config', function () {
    // Check if app already rleased
    if (!has('release_path') || !is_dir(get('release_path'))) {
        throw error("Release directory didn't exist. Is application released yet?");
    }

    // Load configuration file
    $config_file = get('release_path') . '/flyer.yaml';
    $config = [];
    if (file_exists($config_file)) {
        $config = Yaml::parseFile($config_file);
    }

    // Set config variable
    set('config', $config);

    // Load template if specified
    if (isset($config['template']['name'])) {
        $schema = $config['template']['name'];
        $path = __DIR__ . '/../' . str_replace('.', '/', $schema) . '.php';

        // Throw warning if template name error
        if (!file_exists($path)) {
            warning("Template name $schema is invalid");
        }

        // Load template
        require $path;
    }
});