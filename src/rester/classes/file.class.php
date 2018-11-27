<?php
/**
 *	@class		File
 *	@author	    Kevin Park (kevinpark1981<>gmail.com)
 *	@author	    Computer Science in Inje Univ.
 *	@version	1.0	
 *	@brief		파일 컨트롤 클래스.
 *	@date		2018.05.10 - 생성
 *
 */
class File
{
    /**
     * @var array default values
     */
    protected $config = array(
        'upload_path'=>'rester/files',
        'upload_path_detail'=>'Y-m/d',
        'extensions'=>['jpg','png','jpeg','gif','svg','pdf','hwp','doc','docx','xls','xlsx','ppt','pptx','txt'],
        'max_count'=>5,
        'path_group'=>true,
        'upload_tmp'=>true
    );
    protected $module_name; // 호출 모듈명
    protected $data;        // 데이터

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
     * file constructor.
     *
     * @param null|array|File $data 파일데이터
     *
     * @throws Exception
     */
    public function __construct($data=null)
    {
        $this->module_name = cfg::module();
        foreach(cfg::Get('file') as $k=>$v)
        {
            if($k=='extensions') $v = array_filter(explode(',',$v));
            if($v) $this->config[$k] = $v;
        }

        if(is_object($data)) { $this->data = $data->get(); }
        elseif(null !== $data && is_array($data)) $this->data = $data;
    }

    /**
     * 업로드 경로 반환
     * 파일경로 반환에도 사용됨
     *
     * @param string $file 파일명
     * @return string 업로드 경로
     */
    protected function upload_path($file='')
    {
        $path = explode('/',$this->upload_path);

        if($this->path_group)
        {
            if($this->data['file_module']) $path[] = $this->data['file_module'];
            else $path[] = $this->module_name;
        }

        if($this->data['file_datetime'])
            $path = array_merge($path,explode('/',date($this->path_detail,strtotime($this->data['file_datetime']))));
        else
            $path = array_merge($path,explode('/',date($this->path_detail)));

        return $this->gen_path($path,$file);
    }

    /**
     * 파일 업로드
     *
     * 클라이언트에서 전달 받은 파일을 업로드 한다.
     *  - 업로드 하려는 위치의 폴더를 생성한다.
     *  - 단일파일 또는 멀티파일 모두를 지원 하기위한 전처리
     *  - 파일 개수만큼 데이터베이스 레코드를 삽입하고 업로드된 목록을 반환해 줌
     *
     * @param      $form_name
     * @param null $extensions
     *
     * @return File[] 업로드된 파일목록
     * @throws Exception
     */
    public function upload($form_name, $extensions=null)
    {
        if($extensions) $cfg['extensions'] = $extensions;

        // 업로드 폴더 생성
        mkdir($this->upload_path(), 0775, true);
        chmod($this->upload_path(), 0775);

        // 폼이름
        $name = $form_name;

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
                rester::error("허용되지 않는 파일 확장자 입니다. ({$file_ext})");
            }
            // 파일 업로드
            else if(is_uploaded_file($tmp_name))
            {
                $real_file_name = $this->gen_filename($file_name);
                $dest_file = $this->upload_path($real_file_name);

                if(move_uploaded_file($tmp_name, $dest_file))
                {
                    chmod($dest_file, 0664);

                    $uploaded_files[] = new File(array(
                        'file_module'=>$this->module_name,
                        'file_name'=>$file_name,
                        'file_local_name'=>$real_file_name,
                        'file_size'=>$size,
                        'file_type'=>$type
                    ));
                }
            }
        }
        return $uploaded_files;
    }



    /**
     * @param integer $flag
     */
    public function set_tmp($flag)
    {
        $this->tmp = $flag;
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
     * 파일 업로드 후
     * FileUpload::run() 에서 반환되는 인스턴스로 호출가능
     *
     * @param int $fkey 외부연동 테이블 키
     * @param int $owner 파일업로드 유저
     *
     * @return integer
     * @throws Exception
     */
    public function insert($fkey=0, $owner=0)
    {
        $this->data = array_filter($this->data);
        $this->data[self::field_fkey] = $fkey;
        $this->data[self::field_owner] = $owner;
        $this->data[self::field_tmp] = $this->tmp;

        try
        {
            $id = $this->db->insert($this->data);
            $this->data[self::field_no] = $id;
            return $id;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 특정 파일 하나만 불러올 때
     *
     * @param integer $file_no
     *
     * @return array
     * @throws Exception
     */
    public function fetch($file_no)
    {
        try
        {
            return $this->data = $this->db->fetch(" SELECT * FROM {$this->tbn} WHERE file_no={$file_no} LIMIT 1");
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }


    /**
     * @return string
     */
    public function get_uploaded_path()
    {
        return $this->upload_path($this->file_local_name());
    }

    /**
     * @param string $format dateformat
     */
    public function set_path_detail($format)
    {
        $this->path_detail = $format;
    }

    /**
     * @param bool $option
     */
    public function set_group_enable($option)
    {
        $this->path_group = $option;
    }

    /**
     * 경로 생성 함수
     *
     * 경로가 포함된 배열을 받아 경로를 생성해 준다.
     * 옵션에 따라 상대/절대경로를 생성하여 반환해준다.
     *
     * @param array $links 경로배열
     * @param string $file 파일명
     * @return string 생성된 경로
     */
    protected function gen_path($links=array(), $file = '')
    {
        // 경로명 설정에 방해가 될 수 있는 / 제거
        array_walk($links, function(&$item){ $item = str_replace('/','',$item); } );

        // array_unique를 호출하는 이유는 실수로 ''을 더 넣을 경우 경로가 // 가 되는것을 방지하기 위함
        $links = array_filter($links);

        // 최종파일 추가 공백이 들어가면 / 추가됨
        $links[] = $file;

        // 경로 반환
        return implode('/',$links);
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

        do
        {
            $gen_file_name = substr(md5(uniqid(time())),0,12).'_'.$file_name;
        } while(is_file($this->upload_path($gen_file_name)));

        return $gen_file_name;
    }

    /**
     * 파일명으로 검색되는 모든 파일을 다 삭제한다.
     * 생성된 썸네일을 모두 삭제한다.
     * 데이터베이스에 연결되어 있으면 레코드도 삭제함
     *
     * @throws Exception
     */
    public function delete()
    {
        if($this->data['file_local_name'])
        {
            $path = $this->upload_path($this->data['file_local_name']);
            foreach (glob($path.'*') as $v)
            {
                if(is_file($v)) unlink($v);
            }
        }

        // 데이터베이스가 초기화 되어 있고, 파일번호가 있을 경우 레코드 삭제
        if($this->db!==null && $this->file_no())
        {
            try
            {
                // 디비레코드 삭제
                $this->db->simple_delete(self::field_no, $this->file_no());
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function increase_download_count()
    {
        $file_no = $this->file_no();
        $query = " UPDATE `{$this->tbn}` SET `file_download`=(`file_download`+1) WHERE `file_no`={$file_no} LIMIT 1";
        try
        {
            $this->db->update($query);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $desc
     *
     * @throws Exception
     */
    public function update_desc($desc)
    {
        try
        {
            $this->db->simple_update(array(self::field_desc => $desc), self::field_no, $this->file_no());
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 임시파일 -> 일반 파일로 변경
     *
     * @param int $fkey
     *
     * @throws Exception
     */
    public function update_tmp($fkey=0)
    {
        try
        {
            $this->db->simple_update(array(self::field_tmp => 0, self::field_fkey => $fkey), self::field_no, $this->file_no());
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }


}
