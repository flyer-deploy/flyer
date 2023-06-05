<?php

namespace Deployer;

task('web:nginx:post_release', function() {
    echo "something";
});

task('web:nginx:pre_symlink', function() {
    echo "something";
});

task('web:nginx:post_symlink', function() {
    echo "something";
});

task('web:nginx:start', function() {
    echo "starting";
});