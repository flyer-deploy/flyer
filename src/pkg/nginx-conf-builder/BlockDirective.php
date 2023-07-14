<?php

namespace Flyer\NginxConf\ConfBuilder;

class BlockDirective extends Directive
{
    private array $options = [];

    public function __construct(string $name, mixed $params, array $contexts = [])
    {
        parent::__construct($name, $params, $contexts);
    }

    public function open_block()
    {
        $str = $this->name;
        if (!empty($this->params)) {
            $str .= " " . implode_if_array($this->params);
        }
        $str .= ' {';
        return $str;
    }

    public function to_string(): string
    {
        throw new NginxConfBuilderException('Cannot convert block directive to string. If you want to stringify a block directive, use \`NginxConfBuilder\` class.');
    }
}