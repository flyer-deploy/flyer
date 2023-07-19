<?php

namespace Deployer;

use Flyer\Config\Exception\ConfigParseException;
use Flyer\Config;
use function Flyer\Utils\Common\depends;

use Illuminate\Support\Arr;
use Symfony\Component\Yaml;

function run_template_init(array $template_definition)
{
    $init = Arr::get($template_definition, 'init');
    if (is_callable($init)) {
        $init();
    }
}

function register_hooks_from_template(array $template_definition)
{
    $hook_keys = [
        'hook:pre_release',
        'hook:post_release',
        'hook:pre_symlink',
        'hook:post_symlink',
    ];
    foreach ($hook_keys as $key) {
        $hook = Arr::get($template_definition, $key);
        if (is_callable($hook)) {
            task($key, $hook);
        } else if (is_array($hook)) {
            $desc = Arr::get($hook, 'desc');
            $fn = Arr::get($hook, 'fn');
            $task = null;
            if (is_callable($fn)) {
                $task = task($key, $fn);
            }
            if (is_string(($desc))) {
                $task->desc($desc);
            }
        }
    }
}

function load_templates(string $template_name)
{
    $template_files = [
        'web.nginx' => __DIR__ . '/Template/Web/nginx.php',
        'web.litespeed' => __DIR__ . '/Template/Web/litespeed.php',
    ];
    $path = $template_files[$template_name] ?? null;

    // Throw warning if template name error
    if (is_null($path) || !file_exists($path)) {
        throw error("Template name $template_name is invalid.");
    }

    // Load template
    return require $path;
}

task('deploy:load_flyer_yaml', function () {
    depends([
        'release_path'
    ]);

    // Load configuration file
    $config_file = get('release_path') . '/flyer.yaml';
    $config = null;

    if (file_exists($config_file)) {
        try {
            $yaml = Yaml\Yaml::parseFile($config_file);
            $config = Config\Loader::load($yaml);

            if (!is_null($config->template->name)) {
                $template_definition = load_templates($config->template->name);
                register_hooks_from_template($template_definition);
            }
        } catch (Yaml\Exception\ParseException $e) {
            throw new ConfigParseException("Failed to parse yaml config: {$e->getMessage()}", $e);
        }
    } else {
        warning("flyer.yaml not found");
    }

    set('flyer_config', $config);
    set('dependencies', $config->dependencies);
    set('logging', $config->logging);
    set('additional', $config->additional->files);
    set('remove', $config->remove);
    set('shared_dirs', $config->shared->dirs);
    set('shared_files', $config->shared->files);
    set('writable_paths', $config->permission->writable_paths);

    set('template_config', $config->template);
});