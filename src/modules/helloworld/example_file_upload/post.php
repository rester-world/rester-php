<?php if(!defined('__RESTER__')) exit;

use Rester\File\FileUpload;
$body = cfg::Get('response_body_skel');
$body['success'] = true;
$body['msg'] = 'Hello world!! (file_upload example)';
$body['data'] = array(
    '파일업로드 예제',
    '------------------------------------------------------------',
    '== 폼이름 : fname ==',
    '== 업로드된 파일 정보 ==',
    '------------------------------------------------------------',
);

// 파일 업로드 처리
try
{
    $f = new FileUpload('fname');
    foreach ($f->run() as $file)
    {
        $body['data'][] = '파일명 : '.$file->file_name();
        $body['data'][] = '저장된파일명: '.$file->file_path();
        $body['data'][] = '파일크기 : '.$file->file_size();
        $body['data'][] = 'MIME : '.$file->file_type();
        $body['data'][] = '업로드 시각 : '.$file->file_datetime();
        $body['data'][] = '----------------------------------------';
        //$file->delete(); // 파일을 삭제함
    }
}
catch (Exception $e)
{
    $body['success'] = false;
    $body['msg'] = '파일업로드 실패 : '.$e;
}

echo json_encode($body);
