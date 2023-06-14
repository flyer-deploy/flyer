<?php

declare(strict_types=1);

require __DIR__ . '/utils/DeployerOutput.php';

use DeployerOutput;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

function generate_artifact(array $config = [], bool $with_composer_create_project = false, string $directory = '')
{
    $dir = !empty($directory) ? $directory : system('mktemp -d');
    $artifact_dir = $dir . '/artifact';
    system("mkdir -p $artifact_dir");

    $yaml_created = false;
    if (!empty($config)) {
        yaml_emit_file($dir . '/flyer.yaml', $config);
        $yaml_created = true;
    }

    $script_file = "./examples/artifact_creator.sh";
    $script_file = system("readlink -f $script_file");
    $cmd = [
        $script_file,
        "-p $artifact_dir",
        "-z $dir/artifact.zip",
        $yaml_created == true ? "-y $dir/flyer.yaml" : '',
        $with_composer_create_project == true ? '-c 1' : '',
        '> /dev/null 2>&1'
    ];
    system(implode(' ', $cmd));
    system("mkdir -p $dir/deploy");
    return [
        'root' => $dir,
        'deploy' => "$dir/deploy",
        'config' => "$dir/flyer.yaml",
        'zip' => "$dir/artifact.zip"
    ];
}

function generate_env_exports(array $env)
{
    $str = '';
    foreach ($env as $k => $v) {
        $str .= "$k=$v ";
    }
    return $str;
}

function parse_deployer_output(array $output): DeployerOutput
{
    return (new DeployerOutput($output))->parse();
}

function parse_vars(array $env, array $vars)
{
    $parsed = $env;
    foreach ($env as $k => $v) {
        if (($k == 'ARTIFACT_FILE' || $k == 'DEPLOY_PATH') && empty($v)) {
            $parsed[$k] = $vars[$k];
        }
    }
    return $parsed;
}

function stderr(string $message)
{
    fwrite(STDERR, $message);
}

function test_case_asserter(mixed $matcher, string $subject, TestCase $testCase)
{
    $matcher_val = '';
    if (is_array($matcher) && isset($matcher['regex_match'])) {
        $matcher_val = $matcher['regex_match'];
        $testCase->assertMatchesRegularExpression('/' . $matcher_val . '/', $subject);
    } else {
        $testCase->assertEquals($matcher, $subject);
    }
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

        $configs = yaml_parse(file_get_contents(__DIR__ . '/test-cases.yaml', ));

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


        // composer-create once
        $artifact_dir = system('mktemp -d');
        $artifact = generate_artifact([], true, $artifact_dir);

        foreach ($configs as $conf) {
            $artifact = generate_artifact($conf['config'], false, $artifact_dir);

            foreach ($conf['env'] as $env) {
                stderr(PHP_EOL . "------------------ ## ------------------" . PHP_EOL);

                $env_value = $env['value'];

                $vars_parsed_env = parse_vars($env_value, [
                    'ARTIFACT_FILE' => $artifact['zip'],
                    'DEPLOY_PATH' => $artifact['deploy'],
                ]);
                $exports = generate_env_exports($vars_parsed_env);
                $shell = "$exports $dep_bin -f ./src/recipes/flyer.php deploy -vvv";

                stderr("Running command: $shell" . PHP_EOL . PHP_EOL);
                stderr("Config:" . PHP_EOL . PHP_EOL);
                stderr(file_get_contents($artifact['config']) . PHP_EOL . PHP_EOL);

                $output = [];
                $ret = -1;
                exec($shell, $output, $ret);
                $out = parse_deployer_output($output);

                $expected = $env['expected'];
                foreach ($expected as $key => $val) {
                    if ($key == 'exception') {
                        $out->dump(STDERR);
                        $deployer_exception = $out->get_last_exception();
                        $expected_exception = $val;
                        foreach ($expected_exception as $k => $v) {
                            test_case_asserter($v, $deployer_exception[$k], $this);
                        }
                    } elseif ($key == 'result') {
                        $result_type = $val['type'];
                        $params = $val['params'];
                        switch ($result_type) {
                            case 'task_done_successfully':
                                $last_log_line = $out->last_log();
                            // if (!$last_log_line || ($last_log_line && $last_log_line->type !== DeployerLogTypes:: )) {

                            // }
                        }
                    }
                }
            }
        }
    }
}