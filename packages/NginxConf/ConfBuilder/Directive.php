<?php

namespace Flyer\Packages\NginxConf\ConfBuilder;

abstract class Directive
{

    /** @var Directive[]  */
    protected array $directives = [];

    public readonly string $name;
    public readonly mixed $params;

    public function __construct(string $name, mixed $params, array $directives = [])
    {
        $this->name = $name;
        $this->params = $params;
        $this->directives = $directives;
    }

    public function get_directives()
    {
        return $this->directives;
    }

    public function append_directive(Directive $directive)
    {
        $this->directives[] = $directive;
    }

    public function traverse(?Directive $root, array $options = [], callable $cb)
    {
        // stack-based depth-first traverse
        $opts = array_merge(['max_depth' => -1], $options);
        $stack = [['node' => $root ?? $this, 'depth' => 0]];
        $max_depth = $opts['max_depth'];
        while (count($stack)) {
            $n = array_pop($stack);
            $directive = $n['node'];
            $depth = $n['depth'];
            if (is_callable($cb)) {
                $cb_ret = $cb($directive, $depth);
                if ($cb_ret === false) {
                    return;
                }
            }
            $children = $directive->directives;
            if (count($children) == 0) {
                continue;
            }
            if ($max_depth > -1 && $depth >= $max_depth) {
                continue;
            }
            for ($i = count($children) - 1; $i >= 0; $i--) {
                $stack[] = ['node' => $children[$i], 'depth' => $depth + 1];
            }
        }
    }

    abstract function to_string(): string;
}