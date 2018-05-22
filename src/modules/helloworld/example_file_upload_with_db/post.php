<?php if(!defined('__RESTER__')) exit;

use Rester\File\FileUpload;
use Rester\File\FileDB;


$body = cfg::Get('response_body_skel');
$body['success'] = true;
$body['msg'] = 'Hello world!! (file_upload and database insert example)';
$body['data'] = array(
    '파일업로드 & db insert 예제',
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
        try
        {
            $file_db = new FileDB('example_file', $file);
            $id = $file_db->insert();
        }
        catch (Exception $e)
        {
            $body['success'] = false;
            $body['msg'] = '데이터베이스 입력 실패 : '.$e;
            break;
        }

        $body['data'][] = '파일명 : '.$file->file_name();
        $body['data'][] = '저장된파일명: '.$file->file_local_name();
        $body['data'][] = '파일크기 : '.$file->file_size();
        $body['data'][] = 'MIME : '.$file->file_type();
        $body['data'][] = '업로드 시각 : '.$file->file_datetime();
        $body['data'][] = '데이터베이스 입력 키 : '.$id;
        $body['data'][] = '----------------------------------------';
    }
}
catch (Exception $e)
{
    $body['success'] = false;
    $body['msg'] = '파일업로드 실패 : '.$e;
}

echo json_encode($body);
