<?php

namespace Flyer\Packages\NginxConf\ConfBuilder;

class LocationBlockDirective extends BlockDirective
{
    public function __construct(string $modifier, string $path, array $contexts = [])
    {
        $params = [];
        $valid_modifiers = ['=', '~', '~*', '^~'];
        if (!empty($modifier)) {
            if (!in_array($modifier, $valid_modifiers)) {
                throw new NginxConfBuilderException("Invalid modifier '$modifier'.");
            }
            $params[] = $modifier;
        }
        $params[] = $path;
        parent::__construct('location', $params, $contexts);
    }
}