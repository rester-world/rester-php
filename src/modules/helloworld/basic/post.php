<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$body['success'] = true;
$body['msg'] = 'Hello world!! (POST method)';
$body['data'] = array(
    'POST 방식으로 접근하였습니다.',
);

echo json_encode($body);