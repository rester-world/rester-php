<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$body['success'] = true;
$body['msg'] = 'Hello world!! (verify example)';
$body['data'] = array(
    '외부모듈 호출예제',
    '------------------------------------------------------------',
    'result: '.fn('call.external'),
);

echo json_encode($body);
