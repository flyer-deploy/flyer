<?php

namespace Deployer;

task('deploy:release:after', function() {
    echo "do something \n";
});

task('deploy:symlink:after', function() {
    echo "nginx symlink \n";
});