<?php

namespace Deployer;

function create_symlink(string $link, string $target)
{
    run("ln -sfn $target $link");
}