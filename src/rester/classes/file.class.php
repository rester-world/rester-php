<?php

/**
 * Class file
 */
class file
{
    protected $module_name; // 호출 모듈명
    protected $data;        // 데이터
    protected $upload_path; // 파일업로드 경로

    /**
     * @var array default values
     */
    protected $config = array(
        'upload_path'=>'rester/files',
        'upload_path_detail'=>'Y-m/d',
        'extensions'=>['jpg','png','jpeg','gif','svg','pdf','hwp','doc','docx','xls','xlsx','ppt','pptx','txt'],
        'max_count'=>5,
        'path_group'=>true,
        'upload_tmp'=>true,
        'expires'=>172800,
        'cdn'=>'//cdn.rester.kr'
    );

    /**
     * @param string $v
     */
    public function set_upload_path($v) { $this->config['upload_path'] = $v; }

    /**
     * @param array $v
     */
    public function set_extensions($v) { $this->config['extensions'] = $v; }

    /**
     * @param int $v
     */
    public function set_max_count($v) { $this->config['max_count'] = $v; }

    /**
     * @param bool $v
     */
    public function set_path_group($v) { $this->config['path_group'] = $v; }

    /**
     * @param string $v
     */
    public function set_path_detail($v) { $this->config['path_detail'] = $v; }

    /**
     * @param bool $v
     */
    public function set_upload_tmp($v) { $this->config['upload_tmp'] = $v; }

    /**
     * @param null|string $key
     *
     * @return array|string
     */
    public function get($key=null)
    {
        if($key===null) return $this->data;
        return $this->data[$key];
    }

    /**
     * 수동으로 다른 모듈을 설정 할 경우
     * 업로드 경로에 영향을 미친다.
     *
     * @param string $name 모듈명
     */
    public function set_module($name)
    {
        $this->module_name = $name;
    }

    /**
     * @return string
     */
    public function get_uploaded_path()
    {
        return $this->upload_path.$this->data['file_local_name'];
    }

    /**
     * @return string
     */
    protected function gen_upload_path()
    {
        $path = explode('/',$this->config['upload_path']);
        if($this->data)
        {
            if($this->config['path_group']) $path[] = $this->data['file_module'];
            $path = array_merge($path,explode('/',date($this->config['upload_path_detail'],strtotime($this->data['file_datetime']))));
        }
        else
        {
            if($this->config['path_group']) $path[] = $this->module_name;
            $path = array_merge($path,explode('/',date($this->config['upload_path_detail'])));
        }
        // 경로명 설정에 방해가 될 수 있는 / 제거
        array_walk($path, function(&$item){ $item = str_replace('/','',$item); } );
        $path = array_filter($path);

        // 최종파일 추가 공백이 들어가면 / 추가됨
        $path[] = '';

        // 최종 업로드 경로
        return implode('/',$path);
    }

    /**
     * file constructor.
     *
     * @param null|array|File $data 파일데이터
     */
    public function __construct($data=null)
    {
        try
        {
            $this->module_name = cfg::module();
            foreach(cfg::Get('file') as $k=>$v)
            {
                if($k=='extensions') $v = array_filter(explode(',',$v));
                if($v) $this->config[$k] = $v;
            }
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg("Config load failure. ".$e->getMessage());
        }

        if(is_object($data)) { $this->data = $data->get(); }
        elseif(null !== $data && is_array($data)) $this->data = $data;

        // 최종 업로드 경로
        $this->upload_path = $this->gen_upload_path();
    }

    /**
     * 업로드될 파일 경로 생성
     *
     * 웹에서 실행가능한 파일들 방지
     * 중복된 파일이 있을경우 반복해서 파일명 생성
     *
     * @param string $file_name
     * @return string 생성된 파일 경로
     */
    public function gen_filename($file_name)
    {
        // 아래의 문자열이 들어간 파일은 -x 를 붙여서 웹경로를 알더라도 실행을 하지 못하도록 함
        $file_name = preg_replace("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", "$0-x", $file_name);
        // 공백을 _로 변환
        $file_name = str_replace(" ", "_", $file_name);
        $file_name = urlencode($file_name);

        do
        {
            $gen_file_name = substr(md5(uniqid(time())),0,20).'_'.$file_name;
        } while(is_file($this->upload_path.$gen_file_name));

        return $gen_file_name;
    }

    /**
     * 업로드 폴더 생성
     */
    protected function prepare_upload()
    {
        umask(0);
        mkdir($this->upload_path, 0775, true);
    }

    /**
     * 파일 업로드
     *
     * 클라이언트에서 전달 받은 파일을 업로드 한다.
     *  - 업로드 하려는 위치의 폴더를 생성한다.
     *  - 단일파일 또는 멀티파일 모두를 지원 하기위한 전처리
     *  - 파일 개수만큼 데이터베이스 레코드를 삽입하고 업로드된 목록을 반환해 줌
     *
     * @param string $form_name
     *
     * @return array 업로드된 파일목록
     */
    public function upload($form_name)
    {
        $this->prepare_upload();

        // 업로드된 파일
        $uploaded_files = array();

        try
        {
            // 폼이름
            $name = $form_name;

            // 2차원 배열구조로 파일업로드가 되었는지 검사
            if(is_assoc($_FILES[$name]['name']))
            {
                $sub_names = array_keys($_FILES[$name]['name']);
                foreach($sub_names as $subname)
                {
                    $_file_names = $_FILES[$name]['name'][$subname];
                    $_file_types = $_FILES[$name]['type'][$subname];
                    $_file_tmp_name = $_FILES[$name]['tmp_name'][$subname];
                    $_file_size = $_FILES[$name]['size'][$subname];
                    //$_file_erros = $_FILES[$name]['error'][$subname];

                    $upload_file_count = sizeof($_file_names);
                    if($upload_file_count>$this->config['max_count'])
                    {
                        throw new Exception("File upload failed: Max upload file count({$this->config['max_count']}) Upload({$upload_file_count})");
                    }

                    // 파일개수만큼 돌기
                    foreach($_file_names as $k=>$v)
                    {
                        if(!$v) continue;
                        $file_name = $_file_names[$k];
                        $file_ext = array_pop(explode('.',$file_name));
                        $type = $_file_types[$k];
                        $tmp_name = $_file_tmp_name[$k];
                        $size = $_file_size[$k];

                        // 확장자 체크
                        if(!in_array($file_ext,$this->config['extensions']))
                        {
                            throw new Exception("Not allowed file extension. ({$file_ext})");
                        }
                        // 파일 업로드
                        else if(is_uploaded_file($tmp_name))
                        {
                            $real_file_name = $this->gen_filename($file_name);
                            $dest_file = $this->upload_path.$real_file_name;

                            if(move_uploaded_file($tmp_name, $dest_file))
                            {
                                umask(0);
                                chmod($dest_file, 0664);

                                $uploaded_files[$subname][$k] = array(
                                    'file_module'=>$this->module_name,
                                    'file_name'=>$file_name,
                                    'file_local_name'=>$real_file_name,
                                    'file_size'=>$size,
                                    'file_type'=>$type,
                                    'file_datetime'=>date("Y-m-d H:i:s")
                                );
                            }
                        }
                    }
                }
            }
            else
            {
                // 단일파일 => 파일 배열
                if(!is_array($_FILES[$name]['name']) && $_FILES[$name]['name'])
                {
                    $files['name'][0] = $_FILES[$name]['name'];
                    $files['type'][0] = $_FILES[$name]['type'];
                    $files['tmp_name'][0] = $_FILES[$name]['tmp_name'];
                    $files['size'][0] = $_FILES[$name]['size'];
                    $_FILES[$name] = $files;
                }

                $upload_file_count = sizeof($_FILES[$name]['name']);
                if($upload_file_count>$this->config['max_count'])
                {
                    throw new Exception("File upload failed: Max upload file count({$this->config['max_count']}) Upload({$upload_file_count})");
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
                    if(!in_array($file_ext,$this->config['extensions']))
                    {
                        throw new Exception("Not allowed file extension. ({$file_ext})");
                    }
                    // 파일 업로드
                    else if(is_uploaded_file($tmp_name))
                    {
                        $real_file_name = $this->gen_filename($file_name);
                        $dest_file = $this->upload_path.$real_file_name;

                        if(move_uploaded_file($tmp_name, $dest_file))
                        {
                            umask(0);
                            chmod($dest_file, 0664);

                            $uploaded_files[$k] = array(
                                'file_module'=>$this->module_name,
                                'file_name'=>$file_name,
                                'file_local_name'=>$real_file_name,
                                'file_size'=>$size,
                                'file_type'=>$type,
                                'file_datetime'=>date("Y-m-d H:i:s")
                            );
                        }
                    }
                }
            }

        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg($e->getMessage());
            $uploaded_files = false;
        }
        return $uploaded_files;
    }

    /**
     * @param string $url
     * @param string $saveto
     */
    public function grab_file($url,$saveto)
    {
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $raw=curl_exec($ch);
        curl_close ($ch);
        if(file_exists($saveto)){
            unlink($saveto);
        }
        $fp = fopen($saveto,'x');
        fwrite($fp, $raw);
        fclose($fp);
    }

    /**
     * @param $url string
     *
     * @return bool|File 업로드된 파일목록
     */
    public function upload_from_url($url)
    {
        $this->prepare_upload();

        $path = parse_url($url, PHP_URL_PATH);
        $file_name = basename($path);

        $real_file_name = $this->gen_filename($file_name);
        $dest_file = $this->upload_path.$real_file_name;

        // 파일 다운로드
        $this->grab_file($url,$dest_file);

        $uploaded_file = false;
        if(file_exists($dest_file))
        {
            umask(0);
            chmod($dest_file, 0664);
            $type = mime_content_type($dest_file);
            $size = filesize($dest_file);

            $uploaded_file = array(
                'file_module'=>$this->module_name,
                'file_name'=>$file_name,
                'file_local_name'=>$real_file_name,
                'file_size'=>$size,
                'file_type'=>$type
            );
        }
        else
        {
            rester::failure();
            rester::msg("File download failure.");
        }
        return $uploaded_file;
    }

}
