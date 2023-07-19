<?php

namespace Flyer\Config;

use Flyer\Config\Exception\ConfigValidationException;

class Validator
{
    public static function validate(array $config)
    {
        // first stage of rule checking
        $rules1 = [
            // permission
            'permission.writable_paths' => ['array'],
            'permission.writable_paths.*.path' => ['required', 'string'],
            'permission.writable_paths.*.recursive' => ['boolean'],
            'permission.writable_paths.*.files_default_writable' => ['boolean'],

            // template
            'template.name' => [
                'in:web.litespeed,web.nginx,general_process.supervisord,general_process.systemd'
            ],
            'template.params' => ['array'],

            // command hook
            'command_hooks.pre_deploy' => ['nullable', 'string'],
            'command_hooks.post_release' => ['nullable', 'string'],
            'command_hooks.pre_symlink' => ['nullable', 'string'],
            'command_hooks.post_symlink' => ['nullable', 'string'],
            'command_hooks.start' => ['nullable'],

            // shared
            'shared.dirs' => ['array'],
            'shared.dirs.*' => ['string'],
            'shared.files' => ['array'],
            'shared.files.*' => ['string'],

            // dependencies
            'dependencies' => ['array'],
            'dependencies.*' => ['string'],

            // additional files
            'additional.files' => ['array'],
            'additional.files.*' => ['string'],

            // remove
            'remove' => ['array'],
            'remove.*' => ['string'],

            // logging
            'logging.drivers.*.type' => ['in:promtail'],
            'logging.drivers.*.params' => ['array'],
            'logging.rotate.enabled' => ['boolean'],
            'logging.rotate.driver' => ['boolean'],
            'logging.files' => ['array'],
            'logging.files.*.file' => ['required', 'string'],
            'logging.files.*.exclude' => ['string'],
            'logging.files.*.name' => ['string'],
            'logging.files.*.driver' => ['string'],
        ];
        $validator = (new ValidatorFactory)->make($config, $rules1)->stopOnFirstFailure();

        if ($validator->fails()) {
            throw new ConfigValidationException('Failed to validate config', $validator->errors());
        }

        return $validator->validated();
    }
}