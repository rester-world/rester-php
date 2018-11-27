<?php
namespace Rester\File;
use \cfg;
use rester;

/**
 * Class FileUpload
 * kevinpark@webace.co.kr
 *
 * 파일 업로드 클래스
 */
class FileUpload extends  File
{
    protected $form_name;   // 폼이름
    protected $max_count = 5;   // 최대파일 업로드 개수
    protected $extensions = array('jpg','png','gif','jpeg');  // 허용 확장자 명
}
