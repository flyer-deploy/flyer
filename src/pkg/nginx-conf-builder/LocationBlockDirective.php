<?php

namespace Flyer\NginxConf\ConfBuilder;


class LocationBlockDirective extends BlockDirective
{
    public function __construct(string $modifier, string $path, array $contexts = [])
    {
        parent::__construct('location', [$modifier, $path], $contexts);
    }
}