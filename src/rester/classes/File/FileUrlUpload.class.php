<?php
namespace Rester\File;
use \cfg;
use rester;
/**
 * Class FileUrlUpload
 * kevinpark@webace.co.kr
 *
 * @package Rester\File
 */
class FileUrlUpload extends  File
{
    protected $extensions = array('jpg','png','gif','jpeg');  // 허용 확장자 명

    /**
     * fileUpload constructor.
     *
     * @throws \Rester\Exception\RequireModuleName
     */
    public function __construct()
    {
        if(null !== ($v = cfg::Get('file','extensions'))) $this->extensions = explode(',',$v);

        parent::__construct();
    }

    public function set_extension($v)
    {
        if(is_array($v)) $this->extensions = $v;
        else $this->extensions = explode(',',$v);
    }

    public function set_upload_path($v)
    {
        $this->upload_path = $v;
    }

    /**
     * Url을 통한 파일 업로드
     *
     * @param $url string
     *
     * @return bool|File 업로드된 파일목록
     * @throws \Rester\Exception\RequireModuleName
     */
    public function run($url)
    {
        // 업로드 폴더 생성
        mkdir($this->upload_path(), 0775, true);
        chmod($this->upload_path(), 0775);

        $path = parse_url($url, PHP_URL_PATH);
        $file_name = basename($path);

        $real_file_name = $this->gen_filename($file_name);
        $dest_file = $this->upload_path($real_file_name);


        // 이미지 다운로드
        grab_image($url,$dest_file);

        $uploaded_file = false;
        if(file_exists($dest_file))
        {
            chmod($dest_file, 0664);
            $type = mime_content_type($dest_file);
            $size = filesize($dest_file);

            $uploaded_file = new File(array(
                'file_module'=>$this->module_name,
                'file_name'=>$file_name,
                'file_local_name'=>$real_file_name,
                'file_size'=>$size,
                'file_type'=>$type
            ));
        }
        return $uploaded_file;
    }
}
