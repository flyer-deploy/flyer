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
            $str .= " " . Utils::implode_if_array($this->params);
        }
        return $str . ';';
    }

    public function get_directives()
    {
        return [];
    }

    public function append_directive(Directive $directive)
    {
        throw new NginxConfBuilderException('Cannot append directive to a simple directive. Append directive is only possible with block directives.');
    }
}