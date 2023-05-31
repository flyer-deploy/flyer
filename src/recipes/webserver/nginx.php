<?php

namespace Deployer;

function command_hook(string $name) 
{
    switch ($name) {
        case 'post_deploy':
            echo("echo nginx post deploy");
            break;

        case 'pre_symlink':
            echo("echo nginx pre_symlink");
            break;

        case 'post_symlink':
            echo("echo nginx post_symlink");
            break;

        case 'post_start':
            echo("echo nginx starting");
            break;
    }
}