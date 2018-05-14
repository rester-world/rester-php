<?php
namespace Rester\File;
/**
 * Class fileUpload
 * kevinpark@webace.co.kr
 *
 * 파일 업로드 클래스
 */
class fileUpload extends RequireModuleName
{
    protected $form_name;   // 폼이름
    protected $max_count = 5;   // 최대파일 업로드 개수
    protected $extensions = array('jpg','png','gif','jpeg');  // 허용 확장자 명

    /**
     * fileUpload constructor.
     * @param $name form name
     */
    public function __construct($name)
    {
        $this->form_name = $name;
        if(null !== ($v = cfg::Get('file','max_count'))) $this->max_count = $v;
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
     * 파일 업로드
     *
     * 클라이언트에서 전달 받은 파일을 업로드 한다.
     *  - 업로드 하려는 위치의 폴더를 생성한다.
     *  - 단일파일 또는 멀티파일 모두를 지원 하기위한 전처리
     *  - 파일 개수만큼 데이터베이스 레코드를 삽입하고 업로드된 목록을 반환해 줌
     *
     * @return array 업로드된 파일목록
     */
    public function run()
    {
        // 업로드 폴더 생성
        mkdir($this->upload_path(), 0775, true);
        chmod($this->upload_path(), 0775);

        // 폼이름
        $name = $this->form_name;

        // 업로드된 파일
        $uploaded_files = array();

        // 단일파일 => 파일 배열
        if(!is_array($_FILES[$name]['name']) && $_FILES[$name]['name'])
        {
            $files['name'][0] = $_FILES[$name]['name'];
            $files['type'][0] = $_FILES[$name]['type'];
            $files['tmp_name'][0] = $_FILES[$name]['tmp_name'];
            $files['size'][0] = $_FILES[$name]['size'];
            $_FILES[$name] = $files;
        }

        // 파일개수만큼 돌기
        foreach($_FILES[$name]['name'] as $k=>$v)
        {
            $file_name = $_FILES[$name]['name'][$k];
            $file_ext = array_pop(explode('.',$file_name));
            $type = $_FILES[$name]['type'][$k];
            $tmp_name = $_FILES[$name]['tmp_name'][$k];
            $size = $_FILES[$name]['size'][$k];

            // 확장자 체크
            if(!in_array($file_ext,$this->extensions))
            {
                rester::error("허용되지 않는 파일 확장자 입니다. ({$this->file_name})");
            }
            // 파일 업로드
            else if(is_uploaded_file($tmp_name))
            {
                $real_file_name = $this->gen_filename($file_name);
                $dest_file = $this->upload_path($real_file_name);

                if(move_uploaded_file($tmp_name, $dest_file))
                {
                    chmod($dest_file, 0664);

                    $uploaded_files[] = new RequireModuleName(array(
                        'file_module'=>$this->module_name,
                        'file_name'=>$file_name,
                        'file_path'=>$real_file_name,
                        'file_size'=>$size,
                        'file_type'=>$type,
                        'file_datetime'=>date('Y-m-d H:i:s')
                    ));
                }
            }
        }

        return $uploaded_files;
    }

}
