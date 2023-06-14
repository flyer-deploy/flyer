<?php

namespace Deployer;

// Set 'web_litespeed_path' variable
set(
    'web_litespeed_path',
    mandatory(getenv('FLYER_WEB_LITESPEED_PATH'), 'FLYER_WEB_LITESPEED_PATH environment variable')
);

// Set 'web_litespeed_context_dir' variable
set(
    'web_litespeed_context_dir',
    mandatory(getenv('FLYER_WEB_LITESPEED_CONTEXTS_DIR'), 'FLYER_WEB_LITESPEED_CONTEXTS_DIR environment variable')
);

// Get 'config' variable
$config = get('config');

// Check if 'webroot' parameter is specified and assign it to 'web_litespeed_webroot' variable
if (!isset($config['template']['params']['webroot'])) {
    throw wrro("Parameter 'webroot' not specified");
}
set('web_litespeed_webroot', $config['template']['params']['webroot']);

// Assign 'extra_headers' parameter to 'web_litespeed_extra_headers' variable if it exists
$extra_headers = $config['template']['params']['extra_headers'] ?? '';
set('web_litespeed_extra_headers', $extra_headers);

// Assign 'blocked_files' parameter to 'web_litespeed_blocked_files' variable if it exists
$blocked_files = $config['template']['params']['blocked_files'] ?? [];
set('web_litespeed_blocked_files', $blocked_files);

// Run task after 'deploy:release'
task('deploy:release:after', function () {
    $config = get('config');
    $release_path = get('release_path');
    $litespeed_path = get('web_litespeed_path');
    $litespeed_context_dir = get('web_litespeed_context_dir');
    $webroot = get('web_litespeed_webroot');
    $extra_headers = get('web_litespeed_extra_headers');
    $blocked_files = get('web_litespeed_blocked_files');

    run("mkdir -p $litespeed_context_dir");

    $context = "
    context $litespeed_path {
        location $release_path/$webroot
        allowBrowse 1
        rewrite {
          enable 1
        }
        addDefaultCharset off
        phpIniOverride {}
        extraHeaders <<<END_rules
        $extra_headers
        END_rules
    }
    ";

    foreach ($blocked_files as $file) {
        $context .= "
        context $litespeed_path/$file {
            allowBrowse 0
        }
        ";
    }

    $app_id = get('app_id');
    $file = fopen("$litespeed_path/context-$app_id.conf");
    fwrite($file, $context);
    fclose($file);


});

task('deploy:symlink:after', function () {
    run("service lsws restart");
});
