<?php

namespace Flyer\Task\Template\Web;

use function Deployer\task;
use function Deployer\set;
use function Deployer\get;
use function Deployer\writeln;

set(
    'web_nginx_base_path',
    mandatory(getenv('FLYER_WEB_NGINX_BASE_PATH'), 'FLYER_WEB_NGINX_BASE_PATH environment variable')
);

set(
    'web_nginx_locations_dir',
    mandatory(getenv('FLYER_WEB_NGINX_LOCATIONS_DIR'), 'FLYER_WEB_NGINX_LOCATIONS_DIR environment variable')
);

$config = get('config');

// Check if 'webroot' parameter is specified and assign it to 'web_nginx_webroot' variable
set('web_nginx_template_params', function () {
    $config = get('config');

    if (isset($config['template']['params'])) {
        return macro_substitute_arr_deep($config['template']['params'], getenv());
    }
});

function build_directives(Directive &$root_directive, string $location_basepath, array $directives = [])
{
    foreach ($directives as $directive) {
        foreach ($directive as $name => $params) {
            if ($name == 'location') {
                $modifier = $params['modifier'] ?? '';
                $path = $params['path'];
                $prepend_basepath = true;
                if (isset($params['prepend_basepath'])) {
                    $prepend_basepath = $params['prepend_basepath'];
                }
                if ($prepend_basepath === true) {
                    $path = path_join($location_basepath, $params['path']);
                }
                $location_block = new LocationBlockDirective($modifier, $path);
                $root_directive->append_directive($location_block);
                if (isset($params['try_files'])) {
                    $try_files = $params['try_files'];
                    $location_block->append_directive(create_try_files_directive($location_basepath, $try_files));
                }
                if (isset($params['directives'])) {
                    $nested_directives = $params['directives'];
                    build_directives($location_block, path_join($location_basepath, $path), $nested_directives);
                }
            } else if ($name == 'try_files') {
                $root_directive->append_directive(create_try_files_directive($location_basepath, $params));
            } else if ($name == 'error_page') {
                $root_directive->append_directive(create_error_page_directive($location_basepath, $params));
            } else {
                $root_directive->append_directive(new SimpleDirective($name, [$params]));
            }
        }
    }
}

task('hook:post_release', function () {
    $template_params = get('web_nginx_template_params');
    $app_id = get('app_id');


    $web_basepath = get('web_nginx_base_path');

    $block = new LocationBlockDirective('', path_join('/', $web_basepath));

    // webroot
    $webroot = isset($template_params['webroot']) ? $template_params['webroot'] : '';
    $root_path = get('current_path');
    if (!empty($webroot)) {
        $root_path = path_join($root_path, $webroot);
    }
    $block->append_directive(new SimpleDirective('root', [$root_path]));

    $directives = isset($template_params['directives']) ? $template_params['directives'] : [];
    build_directives($block, $web_basepath, $directives);

    $conf_str = (new NginxConfBuilder([$block]))->to_string();

    $conf_basepath = path_join(get('web_nginx_locations_dir'), $app_id);
    set('nginx_conf_release_list', array_map('basename', glob($conf_basepath . '/release.*')));

    $conf_file = path_join($conf_basepath, get('release_name'), 'location.conf');
    $conf_file_dir = dirname($conf_file);
    $conf_current_dir = path_join($conf_basepath, 'current');

    mkdir_if_not_exists($conf_file_dir);

    $config_fd = fopen($conf_file, 'w+');
    fwrite($config_fd, $conf_str);

    // store old release

    // create_symlink($conf_current_dir, dirname($conf_file));
    writeln('{{nginx_conf_release_list}}');

    // test
    // run('nginx -s reload');
});