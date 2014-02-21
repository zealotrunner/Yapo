<?php

namespace Yapo;

/**
 * @codeCoverageIgnore
 */
function debug($var) {
    if (!defined("DEBUG_MODE")) return;

    $is_cli = php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR']);
    $prefix = $is_cli ? "" : '<pre>';
    $suffix = $is_cli ? "\n" : '</pre>';

    echo $prefix;
    if (is_string($var)) {
        echo $var;
    } else {
        print_r($var);
    }
    echo $suffix;
}

// function every($page, $data, $func) {
//     $result = array();
//     for ($i = 0; $i < count($data); $i += $page) {
//         $x = call_user_func($func, array_slice($data, $i, $page, true));
//         if ($x instanceof LazyList) {
//             $result =
//                 $result
//                     ? $result->union($x)
//                     : $x->union();
//         } else {
//             $result += $x; // array_merge, preverve key
//         }
//     }

//     return $result;
// }

function compose($f, $g) {
  return function() use($f,$g) {
    $x = func_get_args();
    return $f(call_user_func_array($g, $x));
  };
}

function is_assoc($a){
   return array_keys($a) !== range(0, count($a) - 1);
}

function i($anything) {
    return $anything;
}