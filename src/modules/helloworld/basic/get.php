<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$ext = new ClassExtension\HelloWorld();

$body['success'] = true;
$body['msg'] = 'Hello world!! (GET method)';
$body['data'] = array(
    'Get 방식으로 접근하였습니다.',
    $ext->run()
);


echo json_encode($body);
