<?php

namespace Flyer\Task;

use function Deployer\task;
use function Deployer\get;
use function Deployer\set;
use function Deployer\warning;
use function Deployer\has;
use function Deployer\writeln;
use function Deployer\error;

use function Flyer\Utils\Common\depends;
use function Flyer\Utils\Common\obtain;

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

    $template_files = [
        'web.nginx' => __DIR__ . '/Template/Web/nginx.php',
        'web.litespeed' => __DIR__ . '/Template/Web/litespeed.php',
    ];
    $path = $template_files[$schema] ?? null;

    // Throw warning if template name error
    if (is_null($path) || !file_exists($path)) {
        throw error("Template name $schema is invalid.");
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
    set('template_config', obtain($config, 'template'));

    load_templates();
});