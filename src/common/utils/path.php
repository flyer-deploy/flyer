<?php

function remove_slash_start(string $str)
{
    if (str_starts_with($str, '/')) {
        return substr($str, 1);
    }
    return $str;
}

function remove_slash_end(string $str)
{
    if (str_ends_with($str, '/')) {
        return substr($str, strlen($str) - 1);
    }
    return $str;
}

function trim_slash(string $str)
{
    return remove_slash_start(remove_slash_end($str));
}

function path_join(string...$paths): string
{
    $resulting_path = '';
    $last_has_trailing_slash = str_ends_with($paths[count($paths) - 1], '/');
    foreach ($paths as $p) {
        $paths_split = explode('/', $p);
        foreach ($paths_split as $p_segment) {
            if (empty($p_segment)) {
                continue;
            }
            $resulting_path .= '/' . trim_slash($p_segment);
        }
    }
    if ($last_has_trailing_slash) {
        $resulting_path .= '/';
    }
    return $resulting_path;
}