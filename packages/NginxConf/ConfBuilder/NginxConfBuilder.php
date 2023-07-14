<?php

namespace Flyer\Packages\NginxConf\ConfBuilder;

class NginxConfBuilder
{
    /** @var Directive[]  */
    private array $directives = [];
    private array $options = [];

    public function __construct(array $directives = [], array $options = ['initial_indent' => 0, 'pretty_print' => true])
    {
        $this->directives = $directives;
        $this->options = array_merge(['initial_indent' => 0,], $options);
    }

    public function to_string()
    {
        $str = '';
        $prev_directive = null;
        $stack_length = 0;
        $last_depth = -1;
        foreach ($this->directives as $directive) {
            $directive->traverse(null, [], function (?Directive $node, int $depth) use (&$str, &$prev_directive, &$stack_length, &$last_depth) {
                $pad = str_repeat("\t", $depth);

                $depth_diff = $depth < $last_depth ? abs($depth - $last_depth) : 0;
                if ($last_depth != -1 && $depth_diff > 0) {
                    while ($depth_diff--) {
                        $stack_length--;
                        $str .= $pad . '}';
                        if ($depth_diff > 0) {
                            $str .= PHP_EOL;
                        }
                    }
                }

                $last_depth = $depth;

                if ($node instanceof SimpleDirective) {
                    $str .= $pad . $node->to_string() . PHP_EOL;
                } else if ($node instanceof BlockDirective) {
                    $stack_length++;
                    $str .= $pad . $node->open_block() . PHP_EOL;
                    if (!count($node->get_directives())) {
                        $str .= $pad . '}' . PHP_EOL;
                        $stack_length--;
                    }
                }
                $prev_directive = $node;
                return true;
            });

            while ($stack_length > 0) {
                $pad = str_repeat("\t", $stack_length - 1);
                $str .= $pad . '}';
                $stack_length--;
                if ($stack_length > 0) {
                    $str .= PHP_EOL;
                }
            }
        }
        return $str;
    }
}