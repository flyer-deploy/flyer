<?php

namespace Deployer;

task('deploy:release:after', function() {
  $ctx_dir = getenv('FLYER_WEB_LITESPEED_CONTEXTS_DIR');
  $content_dir = getenv('$FLYER_WEB_LITESPEED_PATH');

  $config = get('config');
  
  run('mkdir -p $ctx_dir');
});

task('deploy:symlink:after', function() {
    
});