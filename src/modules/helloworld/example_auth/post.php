<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$body['success'] = true;
$body['msg'] = 'Hello world!! (auth example)';
$body['data'] = array(
    'x-auth-id : '.rester::param_header('x-auth-id'),
    'x-auth-token : '.rester::param_header('x-auth-token'),
    'post.auth.php 파일에서 id또는 토큰으로 사용자 인증을 수행가능함',
);

echo json_encode($body);
