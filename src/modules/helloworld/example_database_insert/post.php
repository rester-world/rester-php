<?php if(!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');
$body['success'] = false;

$key = rester::param('key');
$value = rester::param('value');

if($key && $value)
{
    try
    {
        $pdo = db::get();
        $pdo->set_table('example');
        $id = $pdo->insert(array(
            'key'=>rester::param('key'),
            'value'=>rester::param('value')
        ));
        $body['success'] = true;
        $body['msg'] = '입력성공';
        $body['data'] = $id;
    }
    catch (Exception $e)
    {
        $body['msg'] = '데이터베이스 입력오류'.$e;
    }
}
else
{
    $body['msg'] = 'key & value 값이 필요합니다.';
}

echo json_encode($body);
