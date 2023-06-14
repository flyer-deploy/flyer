<?php

namespace Deployer;

set('web_litespeed_path', mandatory(getenv('FLYER_WEB_LITESPEED_PATH'), 'FLYER_WEB_LITESPEED_PATH environment variable'));
set('web_litespeed_context_dir', mandatory(getenv('FLYER_WEB_LITESPEED_CONTEXTS_DIR'), 'FLYER_WEB_LITESPEED_CONTEXTS_DIR environment variable'));

$config = get('config');

$webroot = $config['template']['params']['webroot'] ?? null;
if ($webroot === null) {
    throw error("Parameter 'webroot' not specified");
}
set('web_litespeed_webroot', $webroot);

$extra_headers = $config['template']['params']['extra_headers'] ?? '';
set('web_litespeed_extra_headers', $extra_headers);

$blocked_files = $config['template']['params']['blocked_files'] ?? [];
set('web_litespeed_blocked_files', $blocked_files);

task('deploy:release:after', function () {
    $config = get('config');
    $release_path = get('release_path');

    $litespeed_path = get('web_litespeed_path');
    $litespeed_context_dir = get('web_litespeed_context_dir');

    $webroot = get('web_litespeed_webroot');
    $extra_headers = get('web_litespeed_extra_headers');
    $blocked_files = get('web_litespeed_blocked_files');

    run('mkdir -p $litespeed_context_dir');

    $context = <<<EOD
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
    EOD;

    foreach ($blocked_files as $file) {
        $context .= "\n" . <<<EOD
        context $litespeed_path/$file {
            allowBrowse 0
        }
        EOD;
    }

    $app_id = get('app_id');
    $file = fopen("$litespeed_path/context-$app_id.conf");
    fwrite($file, $context);
    fclose($file);

    
});

task('deploy:symlink:after', function () {
    run("service lsws restart");
});
