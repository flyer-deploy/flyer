<?php

namespace Flyer\Packages\NginxConf\ConfBuilder;

use function Flyer\Utils\Path\path_join;

function is_valid_path($url)
{
    $components = parse_url($url);

    if (!isset($components['path']) || !empty($components['scheme']) || !empty($components['host'])) {
        return false;
    }

    return true;
}

class Utils
{
    public static function create_try_files_directive(string $location_basepath, mixed $try_files)
    {
        if (is_string($try_files)) {
            $try_files = explode(' ', $try_files);
        }

        $last_args = [];

        $last = end($try_files);
        // we need to check that the string starts with '/'. if not, parse_url actually claims something like "=404" is a valid url. (it's a valid try_files value that's not a url)
        // it might be valid (I haven't read the URL specificiation), but it does not suit our case here
        if (str_starts_with($last, '/') && is_valid_path($last)) {
            $path = parse_url($last);
            $path = path_join($location_basepath, $path['path']);
            if (isset($path['query'])) {
                $path .= '?' . $path['query'];
            }
            $last_args[] = $path;
        } else {
            $last_args[] = $last;
        }

        if (count($try_files) == 3) {
            $try_files_params = array_merge(
                array_slice($try_files, 0, count($try_files) - 2),
                $last_args
            );
        } else if (count($try_files) == 2) {
            $try_files_params = array_merge([$try_files[0]], $last_args);
        } else {
            throw new \InvalidArgumentException('Invalid argument count of directive \`try_files\`');
        }
        return new SimpleDirective('try_files', $try_files_params);
    }

    public static function create_error_page_directive(string $location_basepath, mixed $params)
    {
        $prepend_basepath = true;
        if (is_string($params)) {
            $error_page = $params;
        } else if (is_array($params)) {
            $error_page = $params['params'];
            if (isset($params['prepend_basepath'])) {
                $prepend_basepath = $params['prepend_basepath'];
            }
        }
        if (is_string($error_page)) {
            $error_page = explode(' ', $error_page);
        }

        $last = end($error_page);
        $parsed_url = parse_url($last);
        $new_path = $parsed_url['path'];
        if ($prepend_basepath) {
            $new_path = path_join($location_basepath, $parsed_url['path']);
        }
        if (isset($path['query'])) {
            $new_path .= '?' . $parsed_url['query'];
        }
        $error_page_to_append = [];
        if (count($error_page) == 3) {
            $error_page_to_append = array_merge(
                array_slice($error_page, 0, count($error_page) - 2),
                [$new_path]
            );
        } else if (count($error_page) == 2) {
            $error_page_to_append = [$error_page[0], $new_path];
        } else {
            throw new \InvalidArgumentException('Invalid argument count of directive \`error_page\`');
        }
        return new SimpleDirective('error_page', $error_page_to_append);
    }

    public static function implode_if_array(mixed $thing)
    {
        if (is_array($thing)) {
            return implode(' ', $thing);
        } else if (is_string($thing)) {
            return $thing;
        } else {
            throw new \InvalidArgumentException('[implode_if_array]: argument must be either array or string');
        }
    }
}