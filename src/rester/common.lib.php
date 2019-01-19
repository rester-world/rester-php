<?php if(!defined('__RESTER__')) exit;

/**
 * Check associative array
 * 연관 배열인지 검사
 * 숫자가 아닌 키값이 하나라도 있으면 연관배열로 추정
 *
 * @param array $arr
 * @return bool
 */
function is_assoc($arr)
{
    $res = false;
    foreach($arr as $k=>$v) if(!is_numeric($k)) $res = true;
    return $res;
}
