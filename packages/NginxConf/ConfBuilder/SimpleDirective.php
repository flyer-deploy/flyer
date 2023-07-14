<?php

namespace Flyer\Packages\NginxConf\ConfBuilder;


class SimpleDirective extends Directive
{
    public function __construct(string $name, array $params)
    {
        parent::__construct($name, $params, []);
    }

    public function to_string(): string
    {
        $str = $this->name;
        if (!empty($this->params)) {
            $str .= " " . implode_if_array($this->params);
        }
        return $str . ';';
    }

    public function append_directive(Directive $directive)
    {
        throw new NginxConfBuilderException('Cannot append directive to a simple directive. Only block directives are able to do it.');
    }
}