<?php

namespace Deployer;

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
use function Flyer\Utils\ReleaseCleanup\cleanup_old_release;

set(
    'web_nginx_base_path',
    mandatory(getenv('FLYER_WEB_NGINX_BASE_PATH'), 'FLYER_WEB_NGINX_BASE_PATH environment variable')
);

set(
    'web_nginx_locations_dir',
    mandatory(getenv('FLYER_WEB_NGINX_LOCATIONS_DIR'), 'FLYER_WEB_NGINX_LOCATIONS_DIR environment variable')
);

set('web_nginx_template_params', function () {
    $config = get('flyer_config');

    if (isset($config['template']['params'])) {
        return macro_substitute_arr_deep($config['template']['params'], getenv());
    }
});

function build_directives(Directive &$root_directive, string $location_basepath, string $root, array $directives = [])
{
    foreach ($directives as $directive) {
        foreach ($directive as $name => $params) {
            if ($name == 'location') {
                $modifier = $params['modifier'] ?? '';
                $orig_path = $params['path'];
                $new_path = $orig_path;
                $prepend_basepath = true;
                if (isset($params['prepend_basepath'])) {
                    $prepend_basepath = $params['prepend_basepath'];
                }
                if ($prepend_basepath === true) {
                    $new_path = path_join($location_basepath, $params['path']);
                }
                $location_block = new LocationBlockDirective($modifier, $new_path);
                $root_directive->append_directive($location_block);

                // handle nested directives (aka contexts)
                if (isset($params['directives'])) {
                    $nested_directives = $params['directives'];
                    build_directives($location_block, path_join($location_basepath, $orig_path), $root, $nested_directives);
                }

            } else if ($name == 'try_files') {
                $root_directive->append_directive(NginxConfUtils::create_try_files_directive($location_basepath, $params));
            } else if ($name == 'error_page') {
                $root_directive->append_directive(NginxConfUtils::create_error_page_directive($location_basepath, $params));
            } else if ($name == 'alias') {
                $alias_path = $params;
                if (is_array($params)) {
                    $alias_path = $params[1];
                }
                $root_directive->append_directive(new SimpleDirective('alias', path_join($root, $alias_path)));
            } else {
                $root_directive->append_directive(new SimpleDirective($name, [$params]));
            }
        }
    }
}

task('hook:post_release:web_nginx_cleanup', function () {
    $release_dir_list = array_map(function ($release_dir) {
        return path_join(get('web_nginx_conf_basepath'), $release_dir);
    }, get('web_nginx_release_list') ?? []);
    $new_release_dir = get('web_nginx_new_release_path');

    cleanup_old_release($release_dir_list, $new_release_dir, get('app_id'), 0, false);
});

task('hook:post_release', function () {
    $app_id = get('app_id');
    $web_basepath = get('web_nginx_base_path');
    $template_params = get('web_nginx_template_params');

    $block = new LocationBlockDirective('', path_join('/', $web_basepath));

    // webroot
    $webroot = isset($template_params['webroot']) ? $template_params['webroot'] : '';
    $root_path = get('current_path');
    if (!empty($webroot)) {
        $root_path = path_join($root_path, $webroot);
    }
    $block->append_directive(new SimpleDirective('alias', [$root_path]));

    $directives = isset($template_params['directives']) ? $template_params['directives'] : [];
    build_directives($block, $web_basepath, $root_path, $directives);

    $conf_str = (new NginxConfBuilder([$block]))->to_string();

    $conf_basepath = path_join(get('web_nginx_locations_dir'), $app_id);
    set('web_nginx_conf_basepath', $conf_basepath);

    // store old release
    $release_list = array_map('basename', glob(path_join($conf_basepath, "release.*")));
    natsort($release_list);
    set('web_nginx_release_list', $release_list);

    $new_conf_file = path_join($conf_basepath, get('release_name'), 'location.conf');
    $new_conf_file_dir = dirname($new_conf_file);
    set('web_nginx_new_release_path', $new_conf_file_dir);
    mkdir_if_not_exists($new_conf_file_dir);

    $current = path_join($conf_basepath, 'current');
    set('web_nginx_current', $current);

    $prev_release = realpath($current);
    set('web_nginx_previous_release', $prev_release);

    $config_fd = fopen($new_conf_file, 'w+');
    fwrite($config_fd, $conf_str);

    create_symlink($current, $new_conf_file_dir);

    try {
        // test new config
        run('nginx -t');
    } catch (\Exception $e) {
        writeln("Failed validating NGINX config with error: {$e->getMessage()}. Rolling back to previous config, if any.");
        if ($prev_release !== false) {
            if (!test("[ -d $prev_release ]")) {
                throw error("Previous release $prev_release cannot be found. How could it be!?");
            }
            create_symlink($current, $prev_release);
        } else {
            run("rm $current");
            // set current release to empty string so it's picked up in the cleanup step
            set('web_nginx_new_release_path', '');
        }
        throw $e;
    }

    run('nginx -s reload');

    invoke('hook:post_release:web_nginx_cleanup');
});