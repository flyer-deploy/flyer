<?php

namespace Deployer;

use Symfony\Component\Yaml\Yaml;

function load_templates()
{
    if (!has('template_config')) {
        return;
    }

    if (!isset(get('template_config')['name'])) {
        return;
    }

    $schema = get('template_config')['name'];
    $path = __DIR__ . '/../' . str_replace('.', '/', $schema) . '.php';

    // Throw warning if template name error
    if (!file_exists($path)) {
        writeln("Template name $schema is invalid.");
        return;
    }

    // Load template
    require $path;
    writeln("Template $schema loaded.");
}

task('deploy:load_flyer_yaml', function () {
    depends([
        'release_path'
    ]);

    // Load configuration file
    $config_file = get('release_path') . '/flyer.yaml';
    $config = [];

    if (file_exists($config_file)) {
        $config = Yaml::parseFile($config_file);
    } else {
        warning("flyer.yaml not found");
    }

    if ($config == null) {
        $config = [];
    }
    
    set('flyer_config', $config);
    set('dependencies', obtain($config, 'dependencies'));
    set('logging', obtain($config, 'logging'));
    set('additional', obtain($config, 'additional', 'files'));
    set('remove', obtain($config, 'remove'));
    set('shared_dirs', obtain($config, 'shared', 'dirs'));
    set('shared_files', obtain($config, 'shared', 'files'));
    set('writables', obtain($config, 'writables'));


    load_templates();
});
