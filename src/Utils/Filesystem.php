<?php

namespace Flyer\Utils\Filsystem;

use function Deployer\run;

function create_symlink(string $link, string $target)
{
    run("ln -sfn $target $link");
}