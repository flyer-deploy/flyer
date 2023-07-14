<?php

namespace Flyer\Task\Template\Web;

use Deployer as d;

use Flyer\Packages\NginxConf\ConfBuilder\Directive;
use Flyer\Packages\NginxConf\ConfBuilder\LocationBlockDirective;
use Flyer\Packages\NginxConf\ConfBuilder\NginxConfBuilder;
use Flyer\Packages\NginxConf\ConfBuilder\SimpleDirective;
use Flyer\Packages\NginxConf\ConfBuilder\Utils as NginxConfUtils;

use function Flyer\Utils\Common\mandatory;
use function Flyer\Utils\Common\mkdir_if_not_exists;
use function Flyer\Utils\Substitute\macro_substitute_arr_deep;
use function Flyer\Utils\Path\path_join;
use function Flyer\Utils\Filsystem\create_symlink;

d\set(
    'web_nginx_base_path',
    mandatory(getenv('FLYER_WEB_NGINX_BASE_PATH'), 'FLYER_WEB_NGINX_BASE_PATH environment variable')
);

d\set(
    'web_nginx_locations_dir',
    mandatory(getenv('FLYER_WEB_NGINX_LOCATIONS_DIR'), 'FLYER_WEB_NGINX_LOCATIONS_DIR environment variable')
);

d\set('web_nginx_template_params', function () {
    $config = d\get('flyer_config');

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
                    $location_block->append_directive(NginxConfUtils::create_try_files_directive($location_basepath, $try_files));
                }
                if (isset($params['directives'])) {
                    $nested_directives = $params['directives'];
                    build_directives($location_block, path_join($location_basepath, $path), $nested_directives);
                }
            } else if ($name == 'try_files') {
                $root_directive->append_directive(NginxConfUtils::create_try_files_directive($location_basepath, $params));
            } else if ($name == 'error_page') {
                $root_directive->append_directive(NginxConfUtils::create_error_page_directive($location_basepath, $params));
            } else {
                $root_directive->append_directive(new SimpleDirective($name, [$params]));
            }
        }
    }
}

d\task('hook:post_release', function () {
    $app_id = d\get('app_id');
    $web_basepath = d\get('web_nginx_base_path');
    $template_params = d\get('web_nginx_template_params');

    $block = new LocationBlockDirective('', path_join('/', $web_basepath));

    // webroot
    $webroot = isset($template_params['webroot']) ? $template_params['webroot'] : '';
    $root_path = d\get('current_path');
    if (!empty($webroot)) {
        $root_path = path_join($root_path, $webroot);
    }
    $block->append_directive(new SimpleDirective('root', [$root_path]));

    $directives = isset($template_params['directives']) ? $template_params['directives'] : [];
    build_directives($block, $web_basepath, $directives);

    $conf_str = (new NginxConfBuilder([$block]))->to_string();

    $conf_basepath = path_join(d\get('web_nginx_locations_dir'), $app_id);

    // store old release
    $release_list = array_map('basename', glob(path_join($conf_basepath, "release.*")));
    d\set('web_nginx_release_list', natsort($release_list));

    $new_conf_file = path_join($conf_basepath, d\get('release_name'), 'location.conf');
    $new_conf_file_dir = dirname($new_conf_file);
    mkdir_if_not_exists($new_conf_file_dir);

    $current = path_join($conf_basepath, 'current');
    d\set('web_nginx_current', $current);

    $prev_release = realpath($current);
    d\set('web_nginx_previous_release', $prev_release);

    $config_fd = fopen($new_conf_file, 'w+');
    fwrite($config_fd, $conf_str);

    create_symlink($current, $new_conf_file_dir);

    try {
        // test new config
        d\run('nginx -t');
    } catch (\Exception $e) {
        d\writeln("Failed validating NGINX config with error: {$e->getMessage()}. Rolling back to previous config, if any.");
        $current_release = d\get('web_nginx_conf_current_release');
        if ($current_release !== false) {
            if (!d\test("[ -d $current_release ]")) {
                throw d\error("Current release $current_release cannot be found. How could it be!?");
            }
            create_symlink($current, $prev_release);
        } else {
            d\run("rm $current");
        }
        throw $e;
    } finally {

    }

    d\run('nginx -s reload', no_throw: 10);
});