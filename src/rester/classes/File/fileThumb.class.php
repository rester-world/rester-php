<?php
namespace Rester\File;
/**
 *	@class		file
 *	@author		Kevin Park (kevinpark1981<>gmail.com)
 *	@author		Computer Science in Inje Univ.
 *	@version	1.0	
 *	@brief		파일 컨트롤 클래스.
 *	@date		  2010.01.19 - 생성
 *	@date		  2011.04.03 - MVC 패턴으로 설계 완료
 *	@date		  2011.11.18 - 모듈 구조로 변경, Board/Write클래스와 무관 하도록 분리시킴
 *  @date		  2013.06.27 - basic/upload/download 구조로 분리시킴
 *  @date     2016.09.06 - 파일 모듈형식으로 제거하고 static 클래스로 변환함
 *  @date     2017.01.20
 *            클로저를 활용한 단독실행명 클래스로 변환
 *            필수기능은 모두 클래스 내부에 구현
 *            사용자 정의 함수는 이벤트헨들러 형식으로 구현
 *            global_links/file.php 에서 모두 처리되도록 구현함
 *            추가클래스나 추가모듈이 필요없도록 구현
 *            한페이지에 여러개 파일업로드 모듈이 호출될 수 있도록 구현
 *  @date     2017.03.02 - 필드명이나 배열의 키값을 const 상수로 변환 (오류방지)
 *  @date     2017.03.08 - 경로 depth 설정가능하도록 수정 파일업로드가 많은 곳은 
 *                         2017-03/08 까지해서 일별 폴더도 추가
 *                         한 폴더에 파일이 너무 많으면 오류가능성이 높아짐
 *  
 *  ==============
 *  기본구조
 *  --------------
 *  functions
 *  --------------
 *  upload
 *  uploaded_files
 *  download
 *  image
 *  thumb
 *  delete
 *  
 *  --------------
 *  config - fileuploader를 사용하는 모듈 rester.ini 파일에 정의되어야 함
 *  --------------
 *  ----
 *  스킨 및 클래스사용
 *  ----
 *  tbn                  // 파일 데이터베이스 테이블명
 *  kn                   // 데이터베이스에서 부모테이블 키워드명 (예를들어 게시글번호 필드명)
 *  fname                // 폼이름
 *  extensions           // 허용확장자
 *  max.count            // 파일 최대 업로드개수
 *  group                // 파일업로드 그룹 (업로드 폴더에 그룹명이 추가된다)
 *  
 */
class fileThumb
{
  const hotlink = 'pht';
  const gkey_action = 'action'; ///< ajax 호출시 행동을 하는 키값
  const gkey_module = 'm';      ///< ajax 호출시 행동을 하는 키값
  const config_key = 'fileupload'; ///< rester.ini 파일에 사용될 키값
  
  // 배열 키값
  const tbn = 'tbn';
  const fkey = 'fkey';
  const fname = 'fname';
  const group = 'group';
  const depth = 'depth';
  const extensions = 'extensions';
  const max_count = 'max.count';
  const thumb_width = 'thumb.width';
  const thumb_height = 'thumb.height';
  const auth_upload = 'auth.upload';
  const auth_download = 'auth.download';
  const auth_view = 'auth.view';
  const auth_delete = 'auth.delete';
  
  // 데이터베이스 필드명
  const file_no = 'file_no';
  const file_fkey = 'file_fkey';
  const mb_no = 'mb_no';
  const file_tmp = 'file_tmp';
  const file_name = 'file_name';
  const file_path = 'file_path';
  const file_download = 'file_download';
  const file_size = 'file_size';
  const file_type = 'file_type';
  const file_desc = 'file_desc';
  const file_datetime = 'file_datetime';
  
  // action method 종류
  const action_upload = 'upload';
  const action_uploaded = 'uploaded';
  const action_download = 'download';
  const action_image = 'image';
  const action_thumb = 'thumb';
  const action_delete = 'delete';

  private $module_name;    ///< 호출 모듈명
  private $path_module_link; ///< 모듈 ajax 호출경로
  private $path_upload;    ///< 파일 업로드경로
  private $cfg;            ///< 설정정보
  private $err;            ///< 에러코드
  
  // 에러코드 별 메시지
  private $err_code = array
  (
    0x0001 => '모듈명이 올바르지 않습니다.',
    0x0002 => '올바른 action이 아닙니다.',
    0x0004 => '이미지 형식이 아닙니다.',
    0x0008 => '업로드 권한이 없습니다.',
    0x0010 => '다운로드 권한이 없습니다.',
    0x0020 => '보기 권한이 없습니다.',
    0x0040 => '삭제권한이 없습니다.',
    0x0080 => '지원되는 확장자가 아닙니다.',
    0x0100 => '임시파일 업데이트 실패',
    0x0200 => '파일설명 업데이트 실패',
    0x0400 => '파일이 존재하지 않습니다.',
  );
  
  /**
   *  @brief 에러코드 반환
   *  
   *  @return 에러코드
   *  
   *  @details 생성된 에러코드를 반환함
   */
  public function get_error()
  {
    return $this->err;
  }
  
  /**
   *  @brief 에러코드를 문자열로 반환
   *  
   *  @return 에러목록
   *  
   *  @details 에러코드별 메시지를 문자열로 반환함
   */
  public function get_error_str()
  {
    $result = array();
    foreach($this->err_code as $k=>$v)
    {
      if($this->err & $k)
      {
        $result[] = $v;
      }
    }
    return $result;
  }
  
  /**
   *  @brief 성공여부
   *  
   *  @return true / false
   *  
   *  @details 에러코드를 비교하여 성공적으로 실행되었는지 체크
   */
  public function is_success()
  {
    if($this->err == 0) return true;
    return false;
  }
  
  /**
   *  @brief 생성자
   *  
   *  @details 기본설정 세팅
   */
  public function __construct($module_name)
  { 
    // 에러코드 초기화
    $this->err = 0;
    
    // 모듈명 초기화
    $this->module_name = $module_name;
    
    // 모듈호출 경로
    // 절대경로로 반환
    $old = Path::set_absolute();
    $this->path_module_link = Path::module_link('file.php');
    Path::set_absolute($old);
    
    // 설정정보 초기화
    $this->cfg = array();
    
    // 경로 깊이 설정
    $this->cfg[self::depth] = 1;
    
    // 기본 그룹은 없음
    $this->cfg[self::group] = '';
    
    // 기본확장자 : 이미지 업로드
    $this->cfg[self::extensions] = array('jpg','png','jpeg','gif');
  }
  
  /**
   *  @brief 모듈명 설정
   *  
   *  @param [in] $v 모듈명
   *  
   *  @details 경로 생성에 필요한 모듈명 설정
   */
  public function set_module_name($v)
  {
    $this->module_name = $v;
  }
  
  /**
   *  @brief 설정내용 입력
   *  
   *  @param [in] $v 설정내용(배열)
   *  
   *  @details rester.ini 파일안의 설정내용을 받아들임
   */
  public function set_config($v)
  {
    $this->cfg = $v;
  }

  /**
   *  @brief 경로생성 기본함수
   *  
   *  @param [in] $method 행동방법
   *  @param [in] $key 행동방법에 따른 키값
   *  @return 생성된 경로
   *  
   *  @details ajax 호출을 위한 경로를 생성함
   *  /module_link/file.php 파일경로를 반환하며
   *  파라미터로 모듈명, 행동방법, 키값의 파라미터를 생성함
   */
  private function path($method, $key='', $width=0, $height=0)
  {
    $path = false;
    if($this->module_name)
    {
      $param = array();
      $param[self::gkey_module] = $this->module_name;
      $param[self::gkey_action] = $method;
      if($key) $param['key'] = $key;
      if($width) $param['width'] = $width;
      if($height) $param['height'] = $height;
      $path = $this->path_module_link.'?'.Path::gen_param($param);
    }
    return $path;
  }
  
  /**
   *  @brief 파일삭제 경로반환
   *  
   *  @param [in] $key 파일키값
   *  @return 삭제경로
   */
  public function path_delete($key)
  {
    return $this->path(self::action_delete,$key);
  }

  /**
   *  @brief 파일목록 경로반환
   *  
   *  @param [in] $fkey 연동 키값(외래키)
   *  @return 경로
   */
  public function path_uploaded($fkey)
  {
    return $this->path(self::action_uploaded,$fkey);
  }
  
  /**
   *  @brief 파일 업로드 경로반환
   *  
   *  @return 경로
   */
  public function path_upload($fkey)
  {
    return $this->path(self::action_upload,$fkey);
  }
  
  /**
   *  @brief 파일 다운로드 경로반환
   *  
   *  @param [in] $key 파일 키값
   *  @return 경로
   */
  public function path_download($key)
  {
    return $this->path(self::action_download, $key);
  }
  
  /**
   *  @brief 이미지 출력경로
   *  
   *  @param [in] $key 파일 키값
   *  @return 경로
   */
  public function path_image($key)
  {
    return $this->path(self::action_image, $key);
  }
  
  /**
   *  @brief 썸네일 출력경로
   *  
   *  @param [in] $key    파일 키값
   *  @param [in] $width  너비
   *  @param [in] $height 높이
   *  @return 경로
   */
  public function path_thumb($key,$width=0,$height=0)
  {
    return $this->path(self::action_thumb, $key, $width, $height);
  }
  
  /**
   *  @brief 실제 파일경로
   *  
   *  @return [string] 파일경로
   *  
   *  @details 데이터베이스 레코드를 받아 실제 파일 경로를 생성해 줌
   */
  public function get_filepath($recoard='', $bubble=0)
  {
    // depth 옵션에 따라 폴더깊이 설정
    $dateformat = 'Y-m';
    if($this->cfg[self::depth] > 1) $dateformat = 'Y-m/d';
    
    $datetime = date($dateformat);
    
    if(is_array($recoard))
    {
      $datetime = date($dateformat,strtotime($recoard['file_datetime']));
    }
    
    $path = array();
    if($this->cfg[self::group])
      $path[] = $this->cfg[self::group];
    
    $path = array_merge($path,explode('/',$datetime));
    
    for($i=0; $i<$bubble; $i++)
    {
      array_pop($path);
    }
    
    return Path::files($path,$recoard['file_path']);
  }
  
    
  /**
   *  @brief 업로드 파일명 생성
   *  
   *  @param [in] $path 업로드될 폴더명
   *  @param [in] $name 원본파일명
   *  @return 랜덤하게 생성된 서버용 파일명
   *  
   *  @details 웹에서 실행가능한 파일들 방지
   *  중복된 파일이 있을경우 반복해서 파일명 생성
   */
  public function gen_filename($path, $name)
  {
    // 아래의 문자열이 들어간 파일은 -x 를 붙여서 웹경로를 알더라도 실행을 하지 못하도록 함
    $name = preg_replace("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", "$0-x", $name);
    $realname = '';

    do
    {
      $realname = substr(md5(uniqid(time())),0,6).'_'.$name;
    } while(is_file($path.$realname));

    return $realname;
  }
  
  public function get_extensions()
  {
    $extensions = array();
    if(!is_array($_FILES[self::form_name]['name']))
    {
      $extensions[] = substr($_FILES[self::form_name]['name'], (strrpos($_FILES[self::form_name]['name'],'.')+1));
    }
    else
    {
      foreach($_FILES[self::form_name]['name'] as $v)
      {
        $extensions[] = substr($v, (strrpos($v,'.')+1));
      }
    }
    return $extensions;
  }
  
  public function check_extension()
  {
    if(!self::$extensions) return false;
    $exts = explode(',',self::$extensions);
    
    foreach(self::get_extensions() as $v)
    {
      $ret = false;
      foreach($exts as $ext)
      {
        if($v==$ext)
        {
          $ret = true;
          break;
        }
      }
      if(!$ret) return false;
    }
    return true;
  }
  
  /**
   *  @brief ajax 엑션 처리
   *  
   *  @return 엑션 처리 결과
   *  
   *  @details get 변수로 넘어온 method 방식의 엑션을 수행 후 결과 값을 반환함
   *  각 엑션별 권한 검사를 먼저한다.
   */
  public function action()
  {
    $result = '';
    switch(GV::String(self::gkey_action))
    {
      case self::action_delete:
        if($this->cfg[self::auth_delete]) { $result = $this->action_delete(); }
        else
        {
          $this->err |= 0x0040;
          $result = false;
        }
        break;

      case self::action_download:
        if($this->cfg[self::auth_download]) { $result = $this->action_download(); }
        else
        {
          $this->err |= 0x0010;
          $result = false;
        }
        break;
        
      case self::action_image:
        if($this->cfg[self::auth_view]) { $result = $this->action_image(); }
        else
        {
          $this->err |= 0x0020;
          $result = false;
        }
        break;
        
      case self::action_thumb:
        if($this->cfg[self::auth_view]) { $result = $this->action_thumb(); }
        else
        {
          $this->err |= 0x0020;
          $result = false;
        }
        break;

      case self::action_upload:
        if($this->cfg[self::auth_upload])
        {
          if($this->is_upload()) $result = $this->action_upload();
          else $result = $this->action_uploaded();
        }
        else
        {
          $this->err |= 0x0008;
          $result = false;
        }
        break;
      default: $this->err |= 0x0002; // action 오류
    }
    return $result;
  }
  
  /**
   *  @brief 파일 업로드 유무 검사
   *  
   *  @return true or false
   *  
   *  @details 설정된 폼 이름으로 파일이 업로드 되었는지 검사하여 결과반환
   */
  public function is_upload()
  {
    $ret = false;
    $fname = $this->cfg[self::fname];
    if(is_array($_FILES[$fname]['name']))
    {
      if($_FILES[$fname]['name'][0]) $ret = true;
    }
    else
    {
      if($_FILES[$fname]['name']) $ret = true;
    }
    return $ret;
  }
  
  /**
   *  @brief 업로드된 파일목록
   *  
   *  @return mixed array 업로드된 파일목록 반환
   *  
   *  @details fkey 값을 받아 업로드된 파일목록을 데이터베이스에서 가져옴
   */
  public function action_uploaded($fkey=0,$tmp=true,$thumb_width=0,$thumb_height=0)
  {
    if($fkey==0) $fkey = GV::Number('key');
    $list = array();
    foreach($this->db_list($fkey,$tmp) as $v)
    {
      $v['path_direct'] = $this->get_filepath($v);
      $v['path_download'] = $this->path_download($v['file_no']);
      $v['path_delete'] = $this->path_delete($v['file_no']);
      $v['path_image'] = $this->path_image($v['file_no']);
      $v['path_thumb'] = $this->path_thumb($v['file_no'],$thumb_width,$thumb_height);
      $list[] = $v;
    }
    return $list;
  }

  /**
   *  @brief 파일 업로드
   *  
   *  @return 업로드된 파일레코드 목록
   *  
   *  @details 클라이언트에서 전달 받은 파일을 업로드 한다.
   *  - 업로드 하려는 위치의 폴더를 생성한다.
   *  - 단일파일 또는 멀티파일 모두를 지원 하기위한 전처리
   *  - 파일 개수만큼 데이터베이스 레코드를 삽입하고 업로드된 목록을 반환해 줌
   */
  public function action_upload()
  {
    // 업로드 폴더 생성
    mkdir($this->get_filepath(), 0775, true);
    chmod($this->get_filepath(), 0775);
    if($this->cfg[self::depth]>1) chmod($this->get_filepath(null,1), 0775);
    if($this->cfg[self::group]) chmod($this->get_filepath(null,2), 0775);
    
    // 폼이름
    $fname = $this->cfg[self::fname];
    
    
    $uploaded_files = array();
    if(!is_array($_FILES[$fname]['name']) && $_FILES[$fname]['name'])
    {
      $files['name'][0] = $_FILES[$fname]['name'];
      $files['type'][0] = $_FILES[$fname]['type'];
      $files['tmp_name'][0] = $_FILES[$fname]['tmp_name'];
      $files['size'][0] = $_FILES[$fname]['size'];
      $_FILES[$fname] = $files;
    }
    
    // 파일개수만큼 돌기
    foreach($_FILES[$fname]['name'] as $k=>$v)
    {
      $uploaded_files = array();
        
      $file_name = $_FILES[$fname]['name'][$k];
      $file_ext = array_pop(explode('.',$file_name));
      $type = $_FILES[$fname]['type'][$k];
      $tmp_name = $_FILES[$fname]['tmp_name'][$k];
      $size = $_FILES[$fname]['size'][$k];
      
      // 확장자 체크
      if(!in_array($file_ext,$this->cfg[self::extensions]))
      {
        $this->err |= 0x0080;
      }
      // 파일 업로드
      else if(is_uploaded_file($tmp_name))
      {
        $real_file_name = $this->gen_filename($this->get_filepath(), $file_name);
        $dest_file = $this->get_filepath().$real_file_name;

        if(move_uploaded_file($tmp_name, $dest_file))
        {
          chmod($dest_file, 0664);
          
          $recoard = array();
          $recoard['mb_no'] = M::mb_no();
          $recoard['file_fkey'] = GV::Number('key');
          $recoard['file_tmp'] = 1;
          $recoard['file_name'] = $file_name;
          $recoard['file_path'] = $real_file_name;
          $recoard['file_size'] = filesize($dest_file);
          $recoard['file_type'] = mime_content_type($dest_file);
          $recoard['file_datetime'] = date("Y-m-d h:i:s");
          
          // db insert
          $file_no = $this->db_insert(
            $recoard['mb_no'],
            $recoard['file_fkey'],
            $recoard['file_name'],
            $recoard['file_path'],
            $recoard['file_size'],
            $recoard['file_type']
          );
          
          $recoard['file_no'] = $file_no;
          $recoard['path_download'] = $this->path_download($file_no);
          $recoard['path_delete'] = $this->path_delete($file_no);
          $recoard['path_image'] = $this->path_image($file_no);
          $recoard['path_thumb'] = $this->path_thumb($file_no);

          $uploaded_files[] = $recoard;
        }
      }
    }
    return $uploaded_files;
  }
  
  /**
   *  @brief 파일을 다운로드 후 데이터베이스에 넣기
   *  
   *  @return 업로드된 파일레코드 목록
   *  
   *  @details 특정 URL의 파일을 다운로드 후 데이터베이스에 넣는다.
   *  - 업로드 하려는 위치의 폴더를 생성한다.
   *  - 파일 개수만큼 데이터베이스 레코드를 삽입하고 업로드된 목록을 반환해 줌
   */
  public function action_downloadNinsert($fkey, $path, $fname, $desc, $mb_no=1)
  {
    // 업로드 폴더 생성
    mkdir($this->get_filepath(), 0775, true);
    chmod($this->get_filepath(), 0775);
    if($this->cfg[self::depth]>1) chmod($this->get_filepath(null,1), 0775);
    if($this->cfg[self::group]) chmod($this->get_filepath(null,2), 0775);
    
    $real_file_name = $this->gen_filename($this->get_filepath(), $fname);
    $dest_file = $this->get_filepath().$real_file_name;
    
    // 이미지 다운로드
    CUrl::image($path, $dest_file);
    chmod($dest_file, 0664);
    
    $recoard = array();
    $recoard['mb_no'] = $mb_no;
    $recoard['file_fkey'] = $fkey;
    $recoard['file_tmp'] = 1;
    $recoard['file_name'] = $fname;
    $recoard['file_desc'] = $desc;
    $recoard['file_path'] = $real_file_name;
    $recoard['file_size'] = filesize($dest_file);
    $recoard['file_type'] = mime_content_type($dest_file);
    $recoard['file_datetime'] = date("Y-m-d h:i:s");
    
    // db insert
    $file_no = $this->db_insert(
      $recoard['mb_no'],
      $recoard['file_fkey'],
      $recoard['file_name'],
      $recoard['file_path'],
      $recoard['file_size'],
      $recoard['file_type'],
      $recoard['file_desc']
    );
    
    $recoard['file_no'] = $file_no;
    $recoard['path_download'] = $this->path_download($file_no);
    $recoard['path_delete'] = $this->path_delete($file_no);
    $recoard['path_image'] = $this->path_image($file_no);
    $recoard['path_thumb'] = $this->path_thumb($file_no);

    return $recoard;
  }
  
  /**
   *  @brief 이미지 형태의 파일을 읽어들인다.
   *  
   *  @param [in] $path 이미지 경로
   *  @param [in] $echo 이미지 출력여부
   *  @return 이미지 리소스 반환 실패시 false
   *  
   *  @details 파일 형식에 맞게 이미지 리소스를 로드함
   *  이미지 형식이 아닌 파일은 false를 반환하며 에러코드를 남긴다.
   *  $echo 변수에 따라 바로 출력하거나 리소스를 반환한다.
   */
  public function load_image($path, $echo = false)
  {
    $resource = false;
    if(is_file($path))
    {
      switch(mime_content_type($path))
      {
        case 'image/jpeg':
          $resource = imagecreatefromjpeg($path);
          if($echo) imagejpeg($resource);
          break;
        case 'image/png':
          $resource = imagecreatefrompng($path);
          $background = imagecolorallocate($img, 0, 0, 0);
          imagecolortransparent($resource, $background);
          imagealphablending($resource, false);
          imagesavealpha($resource, true);
          if($echo) imagepng($resource);
          break;
        case "image/gif":
          $resource = imagecreatefromgif($path);
          $background = imagecolorallocate($resource, 0, 0, 0);
          imagecolortransparent($resource, $background);
          if($echo) imagegif($resource);
          break;
        default : $this->err |= 0x0004;
      }
    }
    else
    {
      $this->err |= 0x0400;
    }
    return $resource;
  }
  
  /**
   *  @brief 썸네일 이미지 생성
   *  
   *  @param [in] $path_source  원본이미지 경로
   *  @param [in] $thumb_width  썸네일 이미지 너비
   *  @param [in] $thumb_height 썸네일 이미지 높이
   *  @return 썸네일 이미지 리소스
   *  
   *  @details 썸네일 비율 유지한채 축소 여백에는 가장 많이 쓰인 컬러키 값으로 체움
   *  이미 생성된 썸네일은 다시 생성하지 않음
   */
  public function create_thumb($path_source, $thumb_width, $thumb_height)
  {
    // 타겟 이미지 (썸네일)
    $thumb_path = sprintf("%s_%s_%s", $path_source, $thumb_width, $thumb_height);
    
    // 썸네일 이미지 인스턴스
    $target = null;
    
    if(!is_file($thumb_path))
    {
      // 소스이미지
      $source = $this->load_image($path_source);
      list($ori_width, $ori_height, $type, $attr) = getimagesize($path_source);
      
      // 썸네일 비율 및 사이즈 계산
      // 찌그러지지 않도록 비율로 줄인다.
      
      // 1. 원본영상 크기를 긴 사이즈 기준으로 비율축소함
      // 2. 짧은 사이즈 가 썸네일 크기를 초과하지 않으면 바로 적용
      $width = $ori_width;
      $height = $ori_height;
      
      if($width>$height)
      {
        $ratio = $thumb_width/$width;
        $height = $height*$ratio;
        $width = $thumb_width;
        
        // 썸네일 크기 보다 초과될 경우 다시 줄인다.
        if($height>$thumb_height)
        {
          $ratio = $thumb_height/$height;
          $width = $width*$ratio;
          $height = $thumb_height;
        }
      }
      else
      {
        $ratio = $thumb_height/$height;
        $width = $width*$ratio;
        $height = $thumb_height;
        
        // 썸네일 크기 보다 초과될 경우 다시 줄인다.
        if($width>$thumb_width)
        {
          $ratio = $thumb_width/$width;
          $height = $height*$ratio;
          $width = $thumb_width;
        }
      }
      
      // 칠하기 포지션 계산
      $diff_width = abs($thumb_width - $width);
      $diff_height = abs($thumb_height - $height);
      
      $dest_x = 0;
      $dest_y = 0;
      $dest_width = $width;
      $dest_height = $height;

      // 가로 너비가 넓을 경우 가로기준 맞추고 상하에 배경색 칠함
      if($diff_width>$diff_height)
      {
        $dest_x = round($diff_width/2);
      }
      else
      {
        $dest_y = round($diff_height/2);
      }
      
      // 배경을 깔기위한 컬러키 값 뽑기
      // 가장 많이 사용된 컬러 값
      $colors = array();
      for($x = 0; $x < $ori_width; $x+=2) 
      { 
        for($y = 0; $y < $ori_height; $y+=2) 
        {
          $c = imagecolorat($source, $x, $y);
          if(array_key_exists($c, $colors)) $colors[$c]++;
          else $colors[$c] = 1;
        } 
      }
      
      // 정렬하여 가장 많이 사용된 컬러키 값을 뽑아옴
      arsort($colors);
      $bgColorRGB = imagecolorsforindex($source, array_shift(array_keys($colors))); 
          

      $target = @imagecreatetruecolor($thumb_width, $thumb_height);
      imagefill($target,0,0,imagecolorallocate($target,$bgColorRGB['red'],$bgColorRGB['green'],$bgColorRGB['blue']));

      $ret = @imagecopyresampled(
        $target,
        $source,
        $dest_x,
        $dest_y,
        0,
        0,
        $dest_width,
        $dest_height,
        $ori_width,
        $ori_height
      );
      
      // 파일로 쓰기
      @imagejpeg($target, $thumb_path, 100);
      @chmod($thumb_path, 0664); // 추후 삭제를 위하여 파일모드 변경
      
      imagedestroy($source);
    }
    else
    {
      // 타겟이미지
      $target = $this->load_image($thumb_path);
    }
    
    return $target;
  }
  
  
  /**
   *  @brief 원본 이미지 출력
   */
  public function action_image()
  {
    $key = GV::Number('key');
    $image = $this->db_fetch($key);
    
    header('Content-Type: '.$image['file_type']);
    $this->load_image($this->get_filepath($image),true);
    exit;
  }
  
  /**
   *  @brief 썸네일 이미지 출력
   */
  public function action_thumb()
  {
    $key = GV::Number('key');
    if(!($thumb_width = GV::Number('width')))
    {
      $thumb_width = $this->cfg[self::thumb_width];
    }
    if(!($thumb_height = GV::Number('height')))
    {
      $thumb_height = $this->cfg[self::thumb_height];
    }
    
    $image = $this->db_fetch($key);
    $image_path = $this->get_filepath($image);
    
    $thumb = $this->create_thumb($image_path, $thumb_width, $thumb_height);
    
    // 썸네일 이미지 출력
    header('Content-Type: image/jpeg');
    imagejpeg($thumb);
    
    imagedestroy($thumb);
    exit;
  }
  
  /**
   *  @brief 파일삭제
   *  
   *  @return true or false
   *  
   *  @details 주어진 파일 키값으로 파일 삭제
   */
  public function action_delete($key=0)
  {
    if($key==0) $key = GV::Number('key');
    $image = $this->db_fetch($key);
    $image_path = $this->get_filepath($image);
    
    // 파일명으로 검색되는 모든 파일을 다 삭제한다.
    // 생성된 썸네일을 모두 삭제한다.
    $glob = sprintf("%s*",$image_path);
    foreach (glob($glob) as $v)
    {
      if(is_file($v)) unlink($v);
    }

    // 데이터베이스 레코드 삭제
    if(!$this->db_delete($key))
    {
      $this->err |= 0x0040;
      return false;
    }
    return true;
  }
  
  /**
   *  @brief 파일삭제 (모든 그룹)
   *  
   *  @return true or false
   *  
   *  @details 주어진 외래키 값으로 모든 파일 삭제
   */
  public function action_delete_list($fkey=0)
  {
    if($fkey==0) $fkey = GV::Number('key');
    
    foreach($this->db_list($fkey,false) as $v)
    {
      $image_path = $this->get_filepath($v);
    
      // 파일명으로 검색되는 모든 파일을 다 삭제한다.
      // 생성된 썸네일을 모두 삭제한다.
      $glob = sprintf("%s*",$image_path);
      foreach (glob($glob) as $v)
      {
        if(is_file($v)) unlink($v);
      }
    }
    
    // 데이터베이스 레코드 삭제
    if(!$this->db_delete_list($fkey))
    {
      $this->err |= 0x0040;
      return false;
    }
    
    return true;
  }
  
  /**
   *  @brief 임시업로드 파일 업데이트
   *  
   *  @return true or false
   *  
   *  @details fkey 값으로 임시로 업로드된 파일을 업데이트 함
   */
  public function action_update_tmp($key = 0)
  {
    if(!$key) $key = GV::Number('key');

    // 데이터베이스 레코드 업데이트
    if(!$this->db_update_tmp($key,M::mb_no()))
    {
      $this->err |= 0x0100;
      return false;
    }
    return true;
  }
  
  /**
   *  @brief 파일설명 업데이트
   *  
   *  @return true or false
   *  
   *  @details 파일설명 업데이트 POST로 넘어온 file_desc 정보를 통해
   *  파일 설명을 업로드 함
   */
  public function action_update_desc($key = 0)
  {
    if(!$key) $key = GV::Number('key');
    $desc = $_POST['file_desc'];
    $mb_no = M::mb_no();
    
    // 데이터베이스 레코드 업데이트
    foreach($desc as $file_no=>$desc)
    {
      if(!$this->db_update_desc($file_no,$desc,$mb_no))
      {
        $this->err |= 0x0200;
        return false;
      }  
    }
    return true;
  }
  
  /**
   *  @brief 레코드 패치
   *  
   *  @param [in] $key file_no 키값
   *  @return 키값에 따른 레코드
   *  
   *  @details 데이터베이스에서 레코드 하나를 패치해 온다.
   */
  public function db_fetch($key)
  {
    if($key)
    {
      $tbn = $this->cfg[self::tbn];
      $sql = "
        SELECT *
        FROM {$tbn}
        WHERE file_no = {$key}
        LIMIT 1
      ";
      return D::sql_fetch($sql);
    }
    return false;
  }
  
  /**
   *  @brief 파일목록 (외래키)
   *  
   *  @param [in] $key 외래키
   *  @return list array or false
   *  
   *  @details 외래키값을 받아 외래키와 매칭되는 목록을 받아옴
   *  로그인 한 회원의 임시 업로드된 파일도 같이 받아옴
   */
  public function db_list($key, $tmp = true)
  {
    if($key)
    {
      $tbn = $this->cfg[self::tbn];
      $mb_no = M::mb_no();
      $sql = "
        SELECT *
        FROM {$tbn}
        WHERE file_fkey = {$key}
      ";
      if($tmp) $sql .= " OR ( file_tmp=1 AND mb_no={$mb_no} ) ";
      return D::sql_query_list($sql);
    }
    return false;
  }
  
  /**
   *  @brief 레코드 삽입
   *  
   *  @param [in] $mb_no     회원번호
   *  @param [in] $fkey      외래키
   *  @param [in] $file_name 원본파일명
   *  @param [in] $file_path 실제파일명
   *  @param [in] $file_size 파일크기
   *  @param [in] $file_type 파일타입
   *  @return 삽입된 레코드번호 또는 false (실패)
   *  
   *  @details 데이터베이스에 레코드를 삽입하고 삽입된 레코드번호를 반환한다.
   *  함수 파라미터중에 하나라도 잘못넘어오면 실행하지 않고 false를 반환한다.
   */
  public function db_insert($mb_no, $fkey, $file_name, $file_path, $file_size, $file_type, $file_desc='')
  {
    if($mb_no && $fkey && $file_name && $file_path && $file_size && $file_type)
    {
      $clear = array();
      $clear['mb_no'] = $mb_no;
      $clear['file_fkey'] = $fkey;
      $clear['file_tmp'] = 1;
      $clear['file_name'] = $file_name;
      $clear['file_path'] = $file_path;
      $clear['file_size'] = $file_size;
      $clear['file_type'] = $file_type;
      $clear['file_desc'] = $file_desc;
      $clear['file_datetime'] = 'NOW()';
      return D::insertEx($this->cfg[self::tbn], $clear, array('file_datetime'));
    }
    return false;
  }
  
  /**
   *  @brief 레코드 삭제
   *  
   *  @param [in] $key 삭제할 레코드 키 값
   *  
   *  @details 레코드 키값을 받아 데이터베이스 레코드를 삭제함
   */
  public function db_delete($key, $mb_no=0)
  {
    if($key)
    {
      $tbn = $this->cfg[self::tbn];
      $sql = "
        DELETE FROM {$tbn}
        WHERE file_no = {$key}
      ";
      
      if($mb_no) $sql.=" AND mb_no = {$mb_no} ";
      
      $sql.=" LIMIT 1 ";

      return D::sql_query($sql);
    }
    return false;
  }
  
  /**
   *  @brief 레코드 삭제
   *  
   *  @param [in] $key 외래키값
   *  @param [in] $mb_no 회원번호
   *  
   *  @details 레코드 키값을 받아 데이터베이스 레코드를 삭제함
   */
  public function db_delete_list($key, $mb_no=0)
  {
    if($key)
    {
      $tbn = $this->cfg[self::tbn];
      $sql = "
        DELETE FROM {$tbn}
        WHERE file_fkey = {$key}
      ";
      if($mb_no) $sql.=" AND mb_no = {$mb_no} ";

      return D::sql_query($sql);
    }
    return false;
  }
  
  /**
   *  @brief 임시파일 -> 정상파일
   *  
   *  @param [in] $key   파일 외래키값 file_fkey
   *  @param [in] $mb_no 회원번호
   *  @return true or false
   *  
   *  @details 외래키 값과 회원번호를 통해 임시파일을 정상파일로 변경
   *  회원번호를 넣는 이유는 자신이 올린 파일만 업데이트 하기위함
   */
  public function db_update_tmp($key, $mb_no)
  {
    if($key && $mb_no)
    {
      $tbn = $this->cfg[self::tbn];
      $sql = "
        UPDATE {$tbn}
        SET file_tmp = 0, file_fkey = {$key}
        WHERE file_tmp=1
      ";
      if($mb_no) $sql.=" AND mb_no = {$mb_no} ";
      D::sql_query($sql);
      return true;
    }
    return false;
  }
  
  /**
   *  @brief 파일설명 업데이트
   *  
   *  @param [in] $key   파일키값 file_no
   *  @param [in] $desc  파일에 대한 설명
   *  @param [in] $mb_no 회원번호 (회원번호로 권한 검사할 경우)
   *  @return true or false
   *  
   *  @details Details
   */
  public function db_update_desc($key, $desc, $mb_no)
  {
    if($key && $desc)
    {
      $tbn = $this->cfg[self::tbn];
      $sql = "
        UPDATE {$tbn}
        SET file_desc = '{$desc}'
        WHERE mb_no={$mb_no} AND file_no={$key}
        LIMIT 1
      ";
      D::sql_query($sql);
      return true;
    }
    return false;
  }
  
  public function db_update_download($key)
  {
    if($key)
    {
      $tbn = $this->cfg[self::tbn];
      $sql = "
        UPDATE {$tbn}
        SET file_download = (file_download+1)
        WHERE file_no = {$key}
        LIMIT 1
      ";
      D::sql_query($sql);
      return true;
    }
    return false;
  }
	
	/*
	 * 무단 링크를 방지하기 위해 페이지에서 한번 호출해줘야 한다. 
	 * 다이렉트 링크는 이 함수를 호출하지 못하기 때문에 검사하여서
	 * hotlink 이미지를 뿌려준다.
	 * rester.ini 파일의 변수를 이용하여 한페이지당 한번만 호출한다.
	 */
	/**
	 *  @brief 세션을 통한 무단링크 방지
	 *  
	 *  @return Return_Description
	 *  
	 *  @details Details
	 */
	public function Protection()
  {
		if(!$this->Config(self::hotlink)) {
			$this->SetConfig(self::hotlink, '', uniqid());
			$_SESSION[self::hotlink] = $this->Config(self::hotlink);
		}
		return $this;
	}
}


