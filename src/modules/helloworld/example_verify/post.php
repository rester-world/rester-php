<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$body['success'] = true;
$body['msg'] = 'Hello world!! (verify example)';
$body['data'] = array(
    '파라미터 검증 예제',
    '------------------------------------------------------------',
    '== header ==',
    '------------------------------------------------------------',
    'x-auth-id : '.rester::param_header('x-auth-id'),
    'x-auth-token : '.rester::param_header('x-auth-token'),
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
);

echo json_encode($body);
