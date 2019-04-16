<?php if(!defined('__RESTER__')) exit;

rester_response::msg("Verify parameter!");

return [
    'boolean'=>     request_param('test_boolean'),
    'domain'=>      request_param('test_domain'),
    'email'=>       request_param('test_email'),
    'float'=>       request_param('test_float'),
    'int'=>         request_param('test_int'),
    'ip'=>          request_param('test_ip'),
    'mac'=>         request_param('test_mac'),
    'url'=>         request_param('test_url'),
    'datetime'=>    request_param('test_datetime'),
    'date'=>        request_param('test_date'),
    'time'=>        request_param('test_time'),
    'id'=>          request_param('test_id'),
    'array'=>       request_param('test_array'),
    'user_func'=>   request_param('test_user_func'),
];

