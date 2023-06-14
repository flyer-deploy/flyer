<?php

enum DeployerLogTypes
{
    case TASK_RUNNING;
    case TASK_DONE;
    case RUN_IN_HOST;
    case RUN_IN_HOST_EXCEPTION_OCCURRED;
    case RUN_IN_HOST_ERROR_OCCURRED;
    case RUN_IN_HOST_EXCEPTION_STACK_TRACE;
    case TASK_FAILED;
}

class DeployerLog
{
    public readonly DeployerLogTypes $type;
    public readonly string $line;
    public array $data;

    public function __construct(DeployerLogTypes $type, string $line, array $data = [])
    {
        $this->type = $type;
        $this->line = $line;
        $this->data = $data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }
}

class DeployerOutput
{
    private array $logs = [];
    private array $output = [];

    private bool $parsed = false;

    public function __construct(array $output)
    {
        $this->output = $output;
    }

    public function parse()
    {
        if ($this->parsed === true) {
            return $this;
        }

        $output = $this->output;
        $out = $this;

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
            } elseif (preg_match('/^done (.+?) \d+ms$/', $line, $matches)) {
                if ($log !== null) {
                    $out->append($log);
                    $log = null;
                    $log_data = [];

                }

                $out->append(new DeployerLog(DeployerLogTypes::TASK_DONE, $line, [
                    'task_name' => $matches[1]
                ]));
                $previous_line_state = DeployerLogTypes::TASK_DONE;
            } elseif (preg_match('/^\[.+\] (.+)$/', $line, $matches)) {
                // run in host
                $what_is_run = $matches[1];
                $more_matches = [];
                if (preg_match('/^ (.+?)  in (.+\.php) on line (\d+):$/', $what_is_run, $more_matches)) {
                    $class = $more_matches[1];
                    // general error, probably error from command
                    if ($class === 'error') {
                        $type = DeployerLogTypes::RUN_IN_HOST_ERROR_OCCURRED;
                        $log_data['error'] = [
                            'exit_code' => null,
                            'kind' => null
                        ];
                    } else {
                        // if not, then it's an exception
                        $type = DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED;
                        $log_data['exception'] = [
                            'class' => $more_matches[1],
                            'file' => $more_matches[2],
                            'line' => $more_matches[3],
                            'message' => null,
                            'stack_traces' => [],
                        ];
                    }
                    $previous_line_state = $type;
                    $log = new DeployerLog($type, $line, $log_data);
                } elseif (
                    preg_match('/exit code (\d+) \((.+)\)/', $what_is_run, $more_matches)
                    && $previous_line_state == DeployerLogTypes::RUN_IN_HOST_ERROR_OCCURRED
                ) {
                    $log_data['error']['exit_code'] = $more_matches[1];
                    $log_data['error']['kind'] = $more_matches[2];
                    $log->setData($log_data);
                } elseif (empty(trim($line)) && $previous_line_state == DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED) {
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
            } elseif (preg_match('/ERROR: Task (.+?) failed!/', $line)) {
                if ($log !== null) {
                    $out->append($log);
                    $log = null;
                    $log_data = [];

                }

                $out->append(new DeployerLog(DeployerLogTypes::TASK_FAILED, $line));
                $previous_line_state = DeployerLogTypes::TASK_FAILED;
            }
        }

        $this->parsed = true;
        return $this;
    }

    public function parse2()
    {

    }

    public function append(DeployerLog $log)
    {
        $this->logs[] = $log;
    }

    public function get_logs()
    {
        return $this->logs;
    }

    public function last_log()
    {
        return $this->logs[count($this->logs) - 1];
    }

    /**
     * Does the output shows error?
     */
    public function is_error()
    {
        $last = $this->last_log();
        return $last && $last->type == DeployerLogTypes::TASK_FAILED;
    }

    public function get_last_exception()
    {
        for ($i = count($this->logs); $i > 0; $i--) {
            $log = $this->logs[$i];
            if ($log->type === DeployerLogTypes::RUN_IN_HOST_EXCEPTION_OCCURRED) {
                return $log->data['exception'];
            }
        }
    }

    public function get_last_error()
    {
        for ($i = count($this->logs); $i > 0; $i--) {
            $log = $this->logs[$i];
            if ($log->type === DeployerLogTypes::RUN_IN_HOST_ERROR_OCCURRED) {
                return $log->data['error'];
            }
        }
    }

    public function dump($handle)
    {
        fwrite($handle, implode(PHP_EOL, $this->output));
    }
}