<?php if(!defined('__RESTER__')) exit;

try
{
    /**
     * request body
     */
    verify_param('test_user_func', function($value)
    {
        return strpos($value, 'rester')===false?false:$value;
    });

    /**
     * request header
     */
    verify_header('x-auth-user-func', function($value)
    {
        return strpos($value, 'rester')===false?false:$value;
    });

}
catch (\Rester\Exception\ExceptionBase $e)
{
    echo $e;
    exit;
}



