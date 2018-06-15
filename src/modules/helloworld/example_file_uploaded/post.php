<?php if(!defined('__RESTER__')) exit;

use Rester\File\FileList;

$body = cfg::Get('response_body_skel');
$body['success'] = true;
$body['msg'] = 'Hello world!! (file_uploaded example)';
$body['data'] = array(
    '업로드된 파일목록 예제',
    '------------------------------------------------------------',
    '== 업로드된 파일 정보 : 업로드된 파일 중 임시파일 ==',
    '------------------------------------------------------------',
);

// 파일 업로드 처리
try
{
    $f = new FileList();
    $f->set_database_table(cfg('file','table_name'));

    foreach ($f->tmp(0) as $file)
    {
        $body['data'][] = '파일명 : '.$file->file_name();
        $body['data'][] = '저장된파일명: '.$file->file_local_name();
        $body['data'][] = '파일크기 : '.$file->file_size();
        $body['data'][] = 'MIME : '.$file->file_type();
        $body['data'][] = '업로드 시각 : '.$file->file_datetime();
        $body['data'][] = '임시파일 유무 : '.$file->file_tmp();
        $body['data'][] = '----------------------------------------';
        $f->delete();
    }
}
catch (Exception $e)
{
    $body['success'] = false;
    $body['msg'] = '파일업로드 실패 : '.$e;
}

echo json_encode($body);
