<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');

$body['success'] = true;
$body['msg'] = 'Hello world!! (function example)';
$body['data'] = array(
    "모듈 함수 호출",
    "'fn.list.php' 파일 호출에 대한 결과 값 ",
    '------------------------------------------------------------',
);


foreach(fn('list','My2') as $data)
{
    $body['data'][] = $data;
}

echo json_encode($body);
