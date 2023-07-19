<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Flyer\Config\Validator;
use Flyer\Config\Exception\ConfigValidationException;
use Flyer\Config\Loader;

final class ConfigLoaderTest extends TestCase
{
    public function testLoad()
    {
        $config = [
            'template' => [
                'name' => 'web.nginx',
                'params' => []
            ],
            'permission' => [
                'writable_paths' => [
                    [
                        'path' => 'storage',
                        'recursive' => true,
                        'files_default_writable' => true
                    ]
                ]
            ],
            'remove' => [
                'composer.json',
                'composer.lock',
                'phpunit.xml'
            ],
            'logging' => [
                'drivers' => [
                    [
                        'type' => 'promtail'
                    ]
                ],
                'files' => [
                    [
                        'file' => 'storage/logs/**/*.log'
                    ]
                ]
            ]
        ];
        $loaded_config = Loader::load($config);
        $this->assertEquals(
            json_decode(
                <<<JSON
{
    "permission": {
        "writable_paths": []
    },
    "template": {
        "name": "web.nginx",
        "params": []
    },
    "command_hooks": {
        "pre_deploy": "",
        "post_deploy": "",
        "pre_symlink": "",
        "post_symlink": "",
        "start": ""
    },
    "shared": {
        "dirs": [],
        "files": []
    },
    "dependencies": [],
    "additional": {
        "files": []
    },
    "remove": [
        "composer.json",
        "composer.lock",
        "phpunit.xml"
    ],
    "logging": {
        "driver": {
            "type": "",
            "params": []
        }
    }
}
JSON,
                true
            ),
            $loaded_config->to_array()
        );
    }

}