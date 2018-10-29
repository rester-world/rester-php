<?php if(!defined('__RESTER__')) exit;

function test_user_func($value)
{
    return 'test';
    //return strpos($value, 'rester')===false?false:$value;
};

function x_auth_user_func($value)
{
    return strpos($value, 'rester')===false?false:$value;
};
