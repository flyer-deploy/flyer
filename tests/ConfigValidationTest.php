<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Flyer\Config\Validator;
use Flyer\Config\Exception\ConfigValidationException;

final class ConfigValidationTest extends TestCase
{
    public function testGoodConfig()
    {
        $config = [
            'template' => [
                'name' => 'web.nginx',
                'params' => [
                    'foo' => 'bar'
                ]
            ],
            'permission' => [
                'writable_paths' => [
                    [
                        'file' => 'somefile'
                    ],
                    [
                        'file' => 'somefile',
                        'recursive' => false,
                    ],
                    [
                        'file' => 'somefile',
                        'recursive' => false,
                        'files_default_writable' => false,
                    ]
                ],
            ],
            'command_hooks' => [
                'pre_deploy' => null,
                'post_deploy' => 'echo running something',
            ],
        ];
        Validator::validate($config);
        // hacky way to make this test asserts something since there is no way to expect "no exception"
        $this->assertEquals(0, 0);
    }

    private function validate($config, $expected_errors_array)
    {
        try {
            Validator::validate($config);
            throw new Exception('Validator::validate does not throw exception');
        } catch (ConfigValidationException $e) {
            $errors = $e->get_errors();
            $this->assertEquals($expected_errors_array, $errors->toArray());
        }
    }

    public function testBadConfig()
    {
        $bad_config_1 = [
            'template' => [
                'name' => 'web.nginx',
                // this is invalid
                'params' => 'some string'
            ],
        ];
        $this->validate($bad_config_1, [
            'template.params' => ['validation.array']
        ]);


        $bad_config_2 = [
            'permission' => [
                'writable_paths' => [
                    [
                        // file is not defined, should be invalid
                        'recursive' => false,
                    ],

                ]
            ]
        ];
        $this->validate($bad_config_2, [
            'permission.writable_paths.0.file' => ['validation.required']
        ]);

        $bad_config_3 = [
            'command_hooks' => [
                // this should be string
                'pre_deploy' => [
                    'foo' => 'bar'
                ],
                'post_deploy' => 'echo running something',
            ]
        ];
        $this->validate($bad_config_3, [
            'command_hooks.pre_deploy' => ['validation.string']
        ]);

        $bad_config_4 = [
            'shared' => [
                'dirs' => [
                    'correct',
                    // this should be string
                    [
                        'foo' => 'bar'
                    ]
                ],
                'post_deploy' => 'echo running something',
            ]
        ];
        $this->validate($bad_config_4, [
            'shared.dirs.1' => ['validation.string']
        ]);
    }
}