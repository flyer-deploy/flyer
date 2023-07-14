<?php


require __DIR__ . '/../../../src/pkg/nginx-conf-builder/builder.php';

$directive = new LocationBlock('', '/client', [
    new SimpleDirective('alias', ['/var/www/html/public']),
    new LocationBlock('~', '\.php$', [
        new SimpleDirective('fastcgi_index', ['index.php']),
        new SimpleDirective('include', ['snippets/fastcgi_proxy_params.conf']),
        new SimpleDirective('fastcgi_split_path_info', ['^/v2/api(/public/.+\.php)(/.*)?$']),
        new SimpleDirective('fastcgi_param', ['SCRIPT_FILENAME', '$realpath_root$fastcgi_script_name']),
        new SimpleDirective('fastcgi_param', ['SCRIPT_NAME', '/v2/api$fastcgi_script_name']),
        new SimpleDirective('fastcgi_param', ['PHP_SELF', '/v2/api$fastcgi_script_name']),
    ]),
], ['indent' => 0]);