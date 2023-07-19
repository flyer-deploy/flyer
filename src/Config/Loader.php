<?php

namespace Flyer\Config;

use Illuminate\Support\Arr;

function array_walk_with_dot_keys(array $arr, string $key = '', \Closure $callback = null)
{
    foreach ($arr as $arr_key => $value) {
        if (!empty($key)) {
            $appended_key = "$key.$arr_key";
        } else {
            $appended_key = $arr_key;
        }
        if (is_array($value)) {
            array_walk_with_dot_keys($value, $appended_key, $callback);
        } else {
            if (!is_null($callback) && is_callable($callback)) {
                $callback($arr_key, $appended_key, $value, $arr);
            }
        }
    }
}


class Loader
{
    public static function load(array $config): Config
    {
        $validated_config = Validator::validate($config);

        $config_instance = new Config;

        $permission = new Permission;
        $writable_paths = Arr::get($validated_config, 'writable_paths', []);
        foreach ($writable_paths as $value) {
            $writable_path = new WritablePath;
            $writable_path->path = Arr::get($value, 'path');
            $writable_path->recursive = Arr::get($value, 'recursive', false);
            $writable_path->files_default_writable = Arr::get($value, 'files_default_writable', false);
            $permission->writable_paths[] = $writable_path;
        }

        $template = new Template;
        $template->name = Arr::get($validated_config, 'template.name');
        $template->params = Arr::get($validated_config, 'template.params', []);

        $command_hook = new CommandHook;
        $command_hook->pre_deploy = Arr::get($validated_config, 'command_hooks.pre_deploy', '');
        $command_hook->post_deploy = Arr::get($validated_config, 'command_hooks.post_deploy', '');
        $command_hook->pre_symlink = Arr::get($validated_config, 'command_hooks.pre_symlink', '');
        $command_hook->post_symlink = Arr::get($validated_config, 'command_hooks.post_symlink', '');
        $command_hook->start = Arr::get($validated_config, 'command_hooks.start', '');

        $shared = new Shared;
        $shared->dirs = Arr::get($validated_config, 'shared.dirs', []);
        $shared->files = Arr::get($validated_config, 'shared.files', []);

        $additional = new Additional;
        $additional->files = Arr::get($validated_config, 'additional.files', []);

        $logging = new Logging;
        $logging_driver = new LoggingDriver;
        $logging_driver->type = Arr::get($validated_config, 'logging.driver.type', '');
        $logging_driver->params = Arr::get($validated_config, 'logging.driver.params', []);
        $logging->driver = $logging_driver;
        $logging_rotate = new LoggingRotate;
        $logging_rotate->driver = Arr::get($validated_config, 'logging.rotate.driver', '');
        $logging_rotate->enabled = Arr::get($validated_config, 'logging.rotate.enabled', false);

        $config_instance->permission = $permission;
        $config_instance->template = $template;
        $config_instance->command_hooks = $command_hook;
        $config_instance->shared = $shared;
        $config_instance->dependencies = Arr::get($validated_config, 'dependencies', []);
        $config_instance->additional = $additional;
        $config_instance->remove = Arr::get($validated_config, 'remove', []);
        $config_instance->logging = $logging;

        return $config_instance;
    }
}