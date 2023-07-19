<?php

namespace Flyer\Config;

/**
 * These classes are used for type-safety purposes.
 */

abstract class ConfigItem
{
    public function to_array(): array
    {
        $class_members = get_object_vars($this);
        $arr = [];

        foreach ($class_members as $key => $value) {
            if ($value instanceof ConfigItem) {
                $arr[$key] = $value->to_array();
            } else {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }
}

// ---- Permission
class WritablePath extends ConfigItem
{
    public string $path;
    public bool $recursive;
    public bool $files_default_writable;
}

class Permission extends ConfigItem
{
    /** @var WritablePath[]  */
    public array $writable_paths = [];
}

// ---- Template
class Template extends ConfigItem
{
    public string $name;

    public array $params;
}

// ---- Command hooks
class CommandHook extends ConfigItem
{
    public string $pre_deploy;
    public string $post_deploy;
    public string $pre_symlink;
    public string $post_symlink;
    public string $start;
}

// ---- Shared
class Shared extends ConfigItem
{
    /** @var string[]  */
    public array $dirs;
    /** @var string[]  */
    public array $files;
}

// ---- Additional
class Additional extends ConfigItem
{
    /** @var string[]  */
    public array $files;
}

// ---- Logging
class LoggingDriver extends ConfigItem
{
    public string $type;
    public array $params;
}
class LoggingRotate extends ConfigItem
{
    public bool $enabled;
    public string $driver;
}

class Logging extends ConfigItem
{
    public LoggingDriver $driver;
}

class Config extends ConfigItem
{
    public Permission $permission;

    public Template $template;

    public CommandHook $command_hooks;

    public Shared $shared;

    /** @var string[]  */
    public array $dependencies;

    public Additional $additional;


    /** @var string[]  */
    public array $remove;

    public Logging $logging;
}