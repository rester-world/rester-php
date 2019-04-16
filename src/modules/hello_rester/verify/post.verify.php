<?php if(!defined('__RESTER__')) exit;

function test_user_func($value)
{
    return strpos($value, 'rester')===false?false:$value;
};
