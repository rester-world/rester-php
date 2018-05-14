<?php
namespace Rester\File;
/**
 * Class fileDB
 * kevinpark@webace.co.kr
 *
 * 업로드된 파일을 데이터베이스로 저장
 */
class fileDB extends RequireModuleName
{
    protected $schema = null;
    protected $pdo = null;

    public function __construct($data)
    {
        parent::__construct($data);
    }

    public function set_schema($schema)
    {

    }

    public function insert()
    {
        $pdo->insert($data);
    }

    public function fetch()
    {
        $recoard = $pdo->fetch($file_no);
    }

    public function delete()
    {
        // 디비레코드 삭제
        $pdo->delete();
        parent::delete();
    }

    public function update_count()
    {
        $pdo->update();
    }

    public function update_desc()
    {

    }

    public function list_by_fkey()
    {
        $pdo->select();
        //$pdo->sql_query_list('select ');
        // return number of fileDB instance;
    }

    public function list_by_owner()
    {
        //$pdo->sql_query_list('select ');
        // return number of fileDB instance;
    }

    public function list_update_tmp()
    {
        //$pdo->sql_query_list('select ');
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
	
}
