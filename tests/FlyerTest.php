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
    system("$script_file -p $artifact_dir -z $tmp/artifact.zip -y $tmp/flyer.yaml > /dev/null 2>&1");
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
    return (new DeployerOutput($output))->parse();
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

                $expected = $conf['expected'][$i];

                foreach ($expected as $key => $val) {
                    if ($key == 'exception') {
                        $dump_file = system('mktemp');
                        $dump_file_handle = fopen($dump_file, 'aw');
                        echo 'dump file: ' . $dump_file . PHP_EOL;
                        $out->dump(STDERR);
                        $exception = $out->get_last_exception();
                        $this->assertEquals($exception['message'], $val['message']);
                        $this->assertEquals($exception['class'], $val['class']);
                    } elseif ($key == 'result') {
                        $result_type = $val['type'];
                        $params = $val['params'];
                        switch ($result_type) {
                            case 'task_done_successfully':
                                $last_log_line = $out->last_log();
                                // if (!$last_log_line || ($last_log_line && $last_log_line->type !== DeployerLogTypes::)) {

                                // }
                        }
                    }
                }
            }
        }
    }
}
