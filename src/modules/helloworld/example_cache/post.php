<?php if(!defined('__RESTER__')) exit;

// Cache 에 데이터가 없을 경우
if(!($body = cache()))
{
    $body = cfg::Get('response_body_skel');

    $body['success'] = true;
    $body['msg'] = 'Hello world!! (cache example)';
    $body['data'] = array(
        'Cache 예제',
        '------------------------------------------------------------',
        'cached body',
        '5초 마다 시간값이 갱신됨',
        date("Y-m-d H:i:s"),
        '------------------------------------------------------------',
    );
    $body = json_encode($body);
    cache($body,5);
}

echo $body;
