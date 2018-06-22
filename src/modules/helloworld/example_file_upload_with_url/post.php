<?php if(!defined('__RESTER__')) exit;

use Rester\File\FileUrlUpload;
$body = cfg::Get('response_body_skel');
$body['success'] = true;
$body['msg'] = 'Hello world!! (file_upload example)';
$body['data'] = array(
    'URL 파일업로드 예제',
    '------------------------------------------------------------',
    '== 업로드된 파일 정보 ==',
    '------------------------------------------------------------',
);

// 파일 업로드 처리
try
{
    $f = new FileUrlUpload();
    $file = $f->run(rester::param('url'));
    $tbn = cfg('file','table_name');
    $file->set_database_table($tbn);
    $id = $file->insert();
    $body['data'][] = '파일명 : '.$file->file_name();
    $body['data'][] = '저장된파일명: '.$file->file_local_name();
    $body['data'][] = '파일크기 : '.$file->file_size();
    $body['data'][] = 'MIME : '.$file->file_type();
    $body['data'][] = 'Key : '.$id;
    $body['data'][] = '----------------------------------------';
}
catch (Exception $e)
{
    $body['success'] = false;
    $body['msg'] = '파일업로드 실패 : '.$e;
}

echo json_encode($body);
