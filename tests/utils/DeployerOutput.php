<?php

enum DeployerLogTypes
{
    case TASK_RUNNING;
    case TASK_DONE;
    case RUN_IN_HOST;
    case RUN_IN_HOST_EXCEPTION_OCCURRED;
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

    public function __construct()
    {
    }

    public function append(DeployerLog $log)
    {
        $this->logs[] = $log;
    }

    public function last_log()
    {
        return $this->logs[count($this->logs)-1];
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
}
