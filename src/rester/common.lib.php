<?php if(!defined('__RESTER__')) exit;

/**
 * @param array $arr
 *
 * @return bool
 */
function is_assoc($arr)
{
    $res = false;
    foreach($arr as $k=>$v) if(!is_numeric($k)) $res = true;
    return $res;
}
