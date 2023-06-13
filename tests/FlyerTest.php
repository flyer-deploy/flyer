<?php

declare(strict_types=1);

require __DIR__ . '/utils/DeployerOutput.php';

use DeployerOutput;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

function generate_artifact(array $config)
{
    $tmp = system('mktemp -d');
    $artifact_dir = $tmp . '/artifact';
    system("mkdir -p $artifact_dir");
    yaml_emit_file($tmp . '/flyer.yaml', $config);
    $script_file = "./examples/artifact_creator.sh";
    $script_file = system("readlink -f $script_file");
    system("$script_file -p $artifact_dir -z $tmp/artifact.zip -y $tmp/flyer.yaml");
    system("mkdir -p $tmp/deploy");
    return [$tmp, "$tmp/deploy", "$tmp/flyer.yaml", "$tmp/artifact.zip"];
}

function generate_env_exports(array $env)
{
    $str = '';
    foreach ($env as $k => $v) {
        $str .= "export $k=$v\n";
    }
    return $str;
}

function parse_deployer_output(array $output): DeployerOutput
{
    $out = new DeployerOutput();
    $previous_line_state = '';

    $log = null;
    $log_data = [];

    $matches = [];
    foreach ($output as $line) {
        if (preg_match('/^task .+$/', $line)) { // task <task_name>
            if ($log !== null) {
                $out->append($log);
                $log = null;
                $log_data = [];
            }

            $out->append(new DeployerLog(DeployerLogTypes::TASK_RUNNING, $line));
            $previous_line_state = DeployerLogTypes::TASK_RUNNING;
        } elseif (preg_match('/^done .+ \d+ms$/', $line)) {
            if ($log !== null) {
                $out->append($log);
                $log = null;
                $log_data = [];

            }

            $out->append(new DeployerLog(DeployerLogTypes::TASK_DONE, $line));
            $previous_line_state = DeployerLogTypes::TASK_DONE;
        } elseif (preg_match('/^\[.+\] (.+)$/', $line, $matches)) {
            // run in host
            $what_is_run = $matches[1];
            $more_matches = [];
            if (preg_match('/^ (.+?)  in (.+\.php) on line (\d+):$/', $what_is_run, $more_matches)) {
                $type = DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED;
                $log_data['exception'] = [
                    'class' => $more_matches[1],
                    'file' => $more_matches[2],
                    'line' => $more_matches[3],
                    'message' => null,
                    'stack_traces' => [],
                ];
                $log = new DeployerLog($type, $line, $log_data);
            } elseif (empty(trim($line)) && previous_line_state == DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED) {
                // this is an empty line after the exception class or message, skip it
            } elseif (preg_match('/^  (.+)$/', $what_is_run, $more_matches) && $previous_line_state == DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED) {
                // the exception message
                $log_data['exception']['message'] = $more_matches[1];
                $log->setData($log_data);
            } elseif (
                preg_match('/^(#\d+ .+)$/', $what_is_run, $more_matches) &&
                    ($previous_line_state == DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED ||
                        $previous_line_state == DeployerLogTypes::RUN_IN_HOST_EXCEPTION_STACK_TRACE)
            ) {
                $type = DeployerLogTypes::RUN_IN_HOST_EXCEPTION_STACK_TRACE;
                $log_data['exception']['traces'][] = $more_matches[1];
                $log->setData($log_data);
            } else {
                if ($log !== null) {
                    $out->append($log);
                    $log = null;
                    $log_data = [];

                }

                $type = DeployerLogTypes::RUN_IN_HOST;
                $out->append(new DeployerLog($type, $line));
            }
            $previous_line_state = $type;
        } elseif (preg_match('/^ERROR: Task .+? failed!$/', $line)) {
            if ($log !== null) {
                $out->append($log);
                $log = null;
                $log_data = [];

            }

            $out->append(new DeployerLog(DeployerLogTypes::TASK_FAILED, $line));
            $previous_line_state = DeployerLogTypes::TASK_FAILED;
        }
    }

    return $out;
}

function parse_vars(array $env, array $vars)
{
    $parsed = $env;
    foreach ($env as $k => $v) {
        if ($k == 'ARTIFACT_FILE' || $k == 'DEPLOY_PATH') {
            $parsed[$k] = $vars[$k];
        }
    }
    return $parsed;
}

// Blackbox testing by running the dep commands and see the output.

final class FlyerTest extends TestCase
{
    public function testStuffs()
    {
        // some validations
        $dep_bin = getenv('DEP_BIN');
        if ($dep_bin === false) {
            throw new Exception('Please provide DEP_BIN (deployer binary path) environment variable');
        }

        $configs = [
            // a few possible combination of config and env. 'expected' is the expected output
            [
                'config' => [],
                'env' => [
                    [],
                    ['APP_ID' => 'app_id'],
                    [
                        'APP_ID' => 'app_id',
                        'ARTIFACT_FILE' => '${ARTIFACT_FILE}'
                    ],
                    [
                        'APP_ID' => 'app_id',
                        'ARTIFACT_FILE' => '${ARTIFACT_FILE}',
                        'DEPLOY_PATH' => '${DEPLOY_PATH}'
                    ]
                ],
                'expected' => [
                    [
                        'exception' => [
                            'class' => 'Deployer\Exception\ConfigurationException',
                            'message' => 'Please specify APP_ID environment variable'
                        ]
                    ],
                    [
                        'exception' => [
                            'class' => 'Deployer\Exception\ConfigurationException',
                            'message' => 'Please specify ARTIFACT_FILE environment variable'
                        ]
                    ],
                    [
                        'exception' => [
                            'class' => 'Deployer\Exception\ConfigurationException',
                            'message' => 'Please specify DEPLOY_PATH environment variable'
                        ]
                    ],
                    []
                ]
            ],
            // [
            //     'config' => [
            //         'additional_files' => [
            //             '.env'
            //         ]
            //     ],
            //     'env' => [
            //         [],
            //         [
            //             'APP_ID' => 'app_id'
            //         ]
            //     ],
            //     'expected' => ['error']
            // ],
        ];

        $this->assertEquals(1, 1);

        return [
            'configs' => $configs,
            'dep_bin' => $dep_bin,
        ];
    }

    #[Depends('testStuffs')]
    public function testTheFlyer(array $fixtures)
    {
        $configs = $fixtures['configs'];
        $dep_bin = $fixtures['dep_bin'];

        foreach ($configs as $conf) {
            $artifact = generate_artifact($conf['config']);
            foreach ($conf['env'] as $i => $env) {
                $vars_parsed_env = parse_vars($env, [
                    'ARTIFACT_FILE' => $artifact[3],
                    'DEPLOY_PATH' => $artifact[1],
                ]);
                $exports = generate_env_exports($vars_parsed_env);
                $shell = "$exports $dep_bin -f ./src/recipes/flyer.php deploy -vvv";
                $output = [];
                $ret = -1;
                $output_with_status = exec($shell, $output, $ret);
                $out = parse_deployer_output($output);
                $exception = $out->get_last_exception();

                $expected = $conf['expected'][$i];

                foreach ($expected as $key => $val) {
                    if ($key == 'exception') {
                        $this->assertEquals($exception['message'], $val['message']);
                        $this->assertEquals($exception['class'], $val['class']);
                    }
                }
            }
        }
    }
}
