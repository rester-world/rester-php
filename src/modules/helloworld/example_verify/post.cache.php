<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$body['success'] = true;
$body['msg'] = 'Hello Rester! (verify example)';
$body['data'] = array(
    '파라미터 검증 예제 ('.date("Y-m-d H:i:s").')',
    '------------------------------------------------------------',
    '== header ==',
    '------------------------------------------------------------',
    'x-auth-id : '.rester::param_header('x-auth-id'),
    'x-auth-token : '.rester::param_header('x-auth-token'),
    'x-auth-user-func : '.rester::param_header('x-auth-user-func'),
    '------------------------------------------------------------',
    '== POST | json body ==',
    '------------------------------------------------------------',
    'test_boolean : '.rester::param('test_boolean'),
    'test_domain: '.rester::param('test_domain'),
    'test_email: '.rester::param('test_email'),
    'test_float: '.rester::param('test_float'),
    'test_int: '.rester::param('test_int'),
    'test_int_not_check : '.rester::param('test_int_not_check'),
    'test_ip: '.rester::param('test_ip'),
    'test_mac: '.rester::param('test_mac'),
    'test_url: '.rester::param('test_url'),
    'test_user_func: '.rester::param('test_user_func'),
    'test_datetime: '.rester::param('test_datetime'),
    'test_date: '.rester::param('test_date'),
    'test_time: '.rester::param('test_time'),
    'test_id: '.rester::param('test_id'),
    'test_require: '.rester::param('test_require'),
    'test_array: '.json_encode(rester::param('test_array')),
);

echo json_encode($body);
