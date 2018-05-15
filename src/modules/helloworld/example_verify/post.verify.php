<?php if(!defined('__RESTER__')) exit;

verify_param('test_user_func', function($value)
{
    return strpos($value, 'rester')===false?false:$value;
});

verify_header('x-auth-user-func', function($value)
{
    return strpos($value, 'rester')===false?false:$value;
});


