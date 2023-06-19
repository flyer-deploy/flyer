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
set('web_litespeed_webroot', function () {
    if (!isset(get('config')['template']['params']['webroot'])) {
        throw error("Parameter 'webroot' not specified");
    }

    return get('config')['template']['params']['webroot'];
});

// Assign 'blocked_files' parameter to 'web_litespeed_blocked_files' variable if it exists
set('web_litespeed_blocked_files', function () {
    return get('config')['template']['params']['blocked_files'] ?? [];
});

// Assign 'extra_headers' parameter to 'web_litespeed_extra_headers' variable if it exists
set('web_litespeed_extra_headers', function () {
    return get('config')['template']['params']['extra_headers'] ?? '';
});



// Run task after 'deploy:release'
task('hook:post_release', function () {
    $config = get('config');
    $release_path = get('release_path');
    $litespeed_path = get('web_litespeed_path');
    $litespeed_context_dir = get('web_litespeed_context_dir');
    $webroot = get('web_litespeed_webroot');
    $extra_headers = get('web_litespeed_extra_headers');
    $blocked_files = get('web_litespeed_blocked_files');

    run("mkdir -p $litespeed_context_dir");

    $content_extra_headers = "";
    foreach ($extra_headers as $header) {
        $content_extra_headers .= "$header " . $extra_headers[$header];
    }

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
        $content_extra_headers
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

task('hook:post_symlink', function () {
    run("service lsws restart");
});
