<?php
namespace Rester\File;
use \cfg;
use db;
use Exception;
use Rester\Data\Database;
use Rester\Data\Schema;
use Rester\Exception\RequireModuleName;

/**
 *	@class		File
 *	@author	Kevin Park (kevinpark1981<>gmail.com)
 *	@author	Computer Science in Inje Univ.
 *	@version	1.0	
 *	@brief		파일 컨트롤 클래스.
 *	@date		2018.05.10 - 생성
 *
 */
class File
{
    const field_no = 'file_no';         // 파일번호
    const field_fkey = 'file_fkey';     // 연결테이블의 키값
    const field_owner = 'file_owner';   // 파일업로드 유저값
    const field_module = 'file_module'; // 모듈명(경로계산)
    const field_name = 'file_name';     // 파일명(업로드원본파일명)
    const field_local_name = 'file_local_name'; // 저장된 파일명
    const field_download = 'file_download'; // 다운로드 횠수
    const field_size = 'file_size';     // 파일크기
    const field_type = 'file_type';     // 파일 mime-type
    const field_desc = 'file_desc';     // 파일설명
    const field_datetime = 'file_datetime'; // 파일업로드 시간
    const field_tmp = 'file_tmp';       // 임시파일 여부

    /**
     * @var array 테이블 스키마정의
     */
    protected $schema = array(
        self::field_no=>array(
            'type'=>'filter',
            'filter'=>'FILTER_VALIDATE_INT',
        ),
        self::field_fkey=>array(
            'type'=>'filter',
            'filter'=>'FILTER_VALIDATE_INT',
        ),
        self::field_owner=>array(
            'type'=>'filter',
            'filter'=>'FILTER_VALIDATE_INT',
        ),
        self::field_module=>array(
            'type'=>'regexp',
            'regexp'=>'/^[a-z0-9-_]+$/i',
        ),
        self::field_name=>array(
            'type'=>Schema::TYPE_FILENAME,
        ),
        self::field_local_name=>array(
            'type'=>Schema::TYPE_FILENAME,
        ),
        self::field_download=>array(
            'type'=>'filter',
            'filter'=>'FILTER_VALIDATE_INT',
        ),
        self::field_size=>array(
            'type'=>'filter',
            'filter'=>'FILTER_VALIDATE_INT',
        ),
        self::field_type=>array(
            'type'=>'filter',
            'filter'=>'FILTER_SANITIZE_STRING',
        ),
        self::field_desc=>array(
            'type'=>'filter',
            'filter'=>'FILTER_SANITIZE_STRING',
        ),
        self::field_datetime=>array(
            'type'=>'datetime',
        ),
        self::field_tmp=>array(
            'type'=>'regexp',
            'regexp'=>'/^[01]$/i',
        ),
    );

    /**
     * @var array 데이터
     */
    protected $data = array(
        self::field_no=>null,
        self::field_fkey=>null,
        self::field_owner=>null,
        self::field_module=>null,
        self::field_name=>null,
        self::field_local_name=>null,
        self::field_download=>null,
        self::field_size=>null,
        self::field_type=>null,
        self::field_desc=>null,
        self::field_datetime=>null,
        self::field_tmp=>null
    );

    /**
     * @var string|null 테이블명
     */
    protected $tbn = null;
    /**
     * @var int 임시파일여부
     */
    protected $tmp = 1;

    /**
     * @param null|string $key
     *
     * @return array|string
     */
    public function get_data($key=null)
    {
        if($key===null) return $this->data;
        return $this->data[$key];
    }
    /**
     * @return integer 파일 키
     */
    public function file_no() { return $this->data[self::field_no]; }
    /**
     * @return integer
     */
    public function file_fkey() { return $this->data[self::field_fkey]; }
    /**
     * @return integer
     */
    public function file_owner() { return $this->data[self::field_owner]; }
    /**
     * @return string 모듈명
     */
    public function file_module() { return $this->data[self::field_module]; }

    /**
     * @return string 파일명
     */
    public function file_name() { return $this->data[self::field_name]; }
    /**
     * @return string 저장된 파일명
     */
    public function file_local_name() { return $this->data[self::field_local_name]; }
    /**
     * @return integer
     */
    public function file_download() { return $this->data[self::field_download]; }

    /**
     * @return integer 파일크기
     */
    public function file_size() { return $this->data[self::field_size]; }
    /**
     * @return string 파일 mime-type
     */
    public function file_type() { return $this->data[self::field_type]; }

    /**
     * @return string 파일설명
     */
    public function file_desc() { return $this->data[self::field_desc]; }

    /**
     * @return string 파일 업로드 시각
     */
    public function file_datetime() { return $this->data[self::field_datetime]; }
    /**
     * @return integer
     */
    public function file_tmp() { return $this->data[self::field_tmp]; }

    protected $module_name; // 호출 모듈명
    protected $upload_path = 'rester/files'; // 파일 업로드 경로
    protected $path_group = false;  // 업로드 경로에 그룹(모듈)명을 넣을지 옵션
    protected $path_detail = 'Y-m/d'; // 업로드 경로 상세 date format
    /**
     * @var Database
     */
    protected $db = null;

    /**
     * file constructor.
     *
     * @param null|array|File $data 파일데이터
     *
     * @throws RequireModuleName
     */
    public function __construct($data=null)
    {
        $this->module_name = cfg::Get('module');
        if(!$this->module_name) throw new RequireModuleName();

        if(null !== ($v = cfg::Get('file','upload_path'))) $this->upload_path = $v;
        if(null !== ($v = cfg::Get('file','path_group'))) $this->path_group = $v;
        if(null !== ($v = cfg::Get('file','path_detail'))) $this->path_detail= $v;
        if(null !== ($v = cfg('file','upload_tmp'))) $this->tmp=$v;

        if(is_object($data))
        {
            $this->data = $data->get_data();
        }
        elseif(null !== $data && is_array($data)) $this->data = $data;
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
     * 기본 스키마를 바꾸고 싶을 때
     *
     * @param array $schema
     *
     * @throws Exception
     */
    public function set_schema($schema)
    {
        try
        {
            $this->db->set_schema(new Schema($schema));
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $table_name
     *
     * @throws Exception
     */
    public function set_database_table($table_name)
    {
        try
        {
            $this->tbn = $table_name;
            $this->db = db::get();
            $this->db->set_table($this->tbn);
            $this->db->set_schema(new Schema($this->schema));
        }
        catch (Exception $e)
        {
            throw $e;
        }
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
     * @throws Exception
     */
    public function update_tmp()
    {
        try
        {
            $this->db->simple_update(array(self::field_tmp => 0), self::field_no, $this->file_no());
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param int $file_no
     * @param int $timeout
     *
     * @throws Exception
     */
    public function set_cache($file_no, $timeout=3600)
    {
        // TODO redis server 접속여부 확인
        try
        {
            $this->fetch($file_no);
        }
        catch (Exception $e)
        {
            throw new Exception("시스템오류 관리자에게 문의하세요.");
        }
        cacheFile($file_no,$this->get_uploaded_path(),$timeout);
    }

}
