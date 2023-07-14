<?php

namespace Flyer\Utils\Filsystem;

use Deployer as d;

function create_symlink(string $link, string $target)
{
    d\run("ln -sfn $target $link");
}