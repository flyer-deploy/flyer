<?php

namespace Flyer\Utils\Substitute;

// Macro substitute value in a string.
function macro_substitute(string $str, array $data): string
{
    $matches = [];
    $pattern = '/\${(FLYER_[a-zA-Z0-9_]+)}/';
    if (!preg_match_all($pattern, $str, $matches)) {
        return $str;
    }
    // validate data
    foreach ($matches[1] as $value) {
        if (!isset($data[$value])) {
            throw new \Exception("Referenced name '$value' not found when trying to macro substitute");
        }
    }
    // replace
    $new_str = $str;
    foreach ($matches[0] as $i => $value) {
        $new_str = str_replace($value, $data[$matches[1][$i]], $new_str);
    }
    return $new_str;
}

// Macro substitute values in array recursively.
function macro_substitute_arr_deep(array $arr, array $data): array
{
    $new_arr = [];
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $new_arr[$k] = macro_substitute_arr_deep($v, $data);
        } else if (is_string($v)) {
            $new_arr[$k] = macro_substitute($v, $data);
        } else {
            $new_arr[$k] = $v;
        }
    }
    return $new_arr;
}