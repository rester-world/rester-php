<?php if(!defined('__RESTER__')) exit;

use Rester\File\FileImage;

$body = cfg::Get('response_body_skel');
$body['success'] = false;
$body['msg'] = 'Hello world!!';
$body['data'] = array(
    '썸네일출력 실패',
);

if($no = rester::param('no'))
{
    if(!($width = rester::param('w'))) $width = cfg('file','thumb_width');
    if(!($height = rester::param('h'))) $height = cfg('file','thumb_height');

    try
    {
        $f = new FileImage();
        $f->set_database_table(cfg('file','table_name'));
        $f->thumb($no, $width, $height);
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


