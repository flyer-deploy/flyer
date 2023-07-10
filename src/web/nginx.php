<?php

namespace Deployer;

// Set 'web_litespeed_path' variable
set(
    'web_nginx_location',
    mandatory(getenv('FLYER_WEB_NGINX_LOCATION'), 'FLYER_WEB_NGINX_LOCATION environment variable')
);

// Get 'config' variable
$config = get('config');

// Check if 'webroot' parameter is specified and assign it to 'web_nginx_webroot' variable
set('web_nginx_webroot', function () {
    if (!isset(get('config')['template']['params']['webroot'])) {
        throw error("Parameter 'webroot' not specified");
    }

    return get('config')['template']['params']['webroot'];
});

// Assign 'blocked_files' parameter to 'web_nginx_blocked_files' variable if it exists
set('web_nginx_language', function () {
    return get('config')['template']['params']['language'] ?? "generic";
});

task('hook:post_release', function () {
    $config = get('config');
    $webroot = get('web_nginx_webroot');
    $language = get('web_nginx_language');

    $path =  get('web_nginx_location') . '/' . get('app_id') . '/' . get('release_name');
    $current_path = get('web_nginx_location') . '/' . get('app_id') . '/current';

    run("mkdir -p $path");


    // Run this if every validation passed
    run("ln -sfn $path $current_path");
});
