<?php if(!defined('__RESTER__')) exit;

use Rester\File\FileDownload;

$body = cfg::Get('response_body_skel');
$body['success'] = false;
$body['msg'] = 'Hello world!!';
$body['data'] = array(
    '파일다운로드 실패',
);

if($no = rester::param('no'))
{
    try
    {
        $f = new FileDownload();
        $f->set_database_table(cfg('file','table_name'));
        $f->fetch($no); // 파일내용 패치
        $f->increase_download_count(); // 다운로드 카운트 증가
        $f->run();
        exit;
    }
    catch (Exception $e)
    {
        $body['data'][] = ''.$e;
    }
}
else
{
    $body['data'][] = '- 파일번호를 입력하세요.';
}
echo json_encode($body);


