<?php
namespace Rester\File;
use \cfg;
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
    protected $module_name; // 호출 모듈명
    protected $upload_path = 'rester/files'; // 파일 업로드 경로
    protected $path_group = false;  // 업로드 경로에 그룹(모듈)명을 넣을지 옵션
    protected $path_detail = 'Y-m/d'; // 업로드 경로 상세 date format

    // 파일 데이터
    protected $data = array(
        'file_module'=>null,
        'file_name'=>null,
        'file_local_name'=>null,
        'file_size'=>null,
        'file_type'=>null,
        'file_desc'=>null,
        'file_datetime'=>null
    );

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

        if(is_object($data))
        {
            $this->data['file_module'] = $data->file_module();
            $this->data['file_name'] = $data->file_name();
            $this->data['file_local_name'] = $data->file_local_name();
            $this->data['file_size'] = $data->file_size();
            $this->data['file_type'] = $data->file_type();
            $this->data['file_desc'] = $data->file_desc();
            $this->data['file_datetime'] = $data->file_datetime();
        }
        elseif(null !== $data) $this->data = $data;
    }

    /**
     * @return string 모듈명
     */
    public function file_module() { return $this->data['file_module']; }

    /**
     * @return string 파일명
     */
    public function file_name() { return $this->data['file_name']; }

    /**
     * @return string 저장된 파일명
     */
    public function file_local_name() { return $this->data['file_local_name']; }

    /**
     * @return integer 파일크기
     */
    public function file_size() { return $this->data['file_size']; }

    /**
     * @return string 파일 mime-type
     */
    public function file_type() { return $this->data['file_type']; }

    /**
     * @return string 파일설명
     */
    public function file_desc() { return $this->data['file_desc']; }

    /**
     * @return datetime 파일 업로드 시각
     */
    public function file_datetime() { return $this->data['file_datetime']; }

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
        array_walk($links, function(&$item, $key){ $item = str_replace('/','',$item); } );

        // array_unique를 호출하는 이유는 실수로 ''을 더 넣을 경우 경로가 // 가 되는것을 방지하기 위함
        $links = array_unique($links);

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
    }

}
