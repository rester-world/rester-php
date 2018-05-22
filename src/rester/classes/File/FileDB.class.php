<?php
namespace Rester\File;
use db;
use Rester\Data\Database;
use Rester\Data\Schema;

/**
 * Class fileDB
 * kevinpark@webace.co.kr
 *
 * 업로드된 파일을 데이터베이스로 저장
 */
class FileDB extends File
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
            'type'=>'filter',
            'filter'=>'FILTER_VALIDATE_BOOLEAN',
        ),
    );

    /**
     * @var Database
     */
    protected $db = null;

    /**
     * FileDB constructor.
     *
     * @param array $data
     * @param string $table_name
     *
     * @throws \Exception
     */
    public function __construct($table_name, $data)
    {
        try
        {
            $this->db = db::get();
            $this->db->set_table($table_name);
            $this->db->set_schema(new Schema($this->schema));
        }
        catch (\Exception $e)
        {
            throw $e;
        }

        parent::__construct($data);
    }

    /**
     * 기본 스키마를 바꾸고 싶을 때
     *
     * @param array $schema
     *
     * @throws \Exception
     */
    public function set_schema($schema)
    {
        try
        {
            $this->db->set_schema(new Schema($schema));
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param int $fkey
     * @param int $owner
     *
     * @return integer
     * @throws \Exception
     */
    public function insert($fkey=0, $owner=0)
    {
        $this->data = array_filter($this->data);
        $this->data[self::field_fkey] = $fkey;
        $this->data[self::field_owner] = $owner;
        $this->data[self::field_tmp] = 1;

        try
        {
            $id = $this->db->insert($this->data);
            $this->data[self::field_no] = $id;
            return $id;
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param integer $file_no
     *
     * @return array
     * @throws \Exception
     */
    public function fetch($file_no)
    {
        try
        {
            return $this->data = $this->db->fetch(" SELECT * FROM example_file WHERE file_no={$file_no} LIMIT 1");
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        try
        {
            // 디비레코드 삭제
            $this->db->simple_delete(self::field_no, $this->file_no());

            // 로컬 파일 삭제
            parent::delete();
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    public function update_count()
    {
        //$pdo->update();
    }

    public function update_desc($desc)
    {

    }

    public function list_by_fkey()
    {
        //$pdo->select();
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
     * @return string 파일 키
     */
    public function file_no() { return $this->data[self::field_no]; }



  /**
   *  @brief 파일목록 (외래키)
   *  
   *  @param [in] $key 외래키
   *  @return list array or false
   *  
   *  @details 외래키값을 받아 외래키와 매칭되는 목록을 받아옴
   *  로그인 한 회원의 임시 업로드된 파일도 같이 받아옴
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
   */

  /**
   *  @brief 레코드 삭제
   *  
   *  @param [in] $key 삭제할 레코드 키 값
   *  
   *  @details 레코드 키값을 받아 데이터베이스 레코드를 삭제함
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
   */

  /**
   *  @brief 레코드 삭제
   *  
   *  @param [in] $key 외래키값
   *  @param [in] $mb_no 회원번호
   *  
   *  @details 레코드 키값을 받아 데이터베이스 레코드를 삭제함
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
   */

  /**
   *  @brief 임시파일 -> 정상파일
   *  
   *  @param [in] $key   파일 외래키값 file_fkey
   *  @param [in] $mb_no 회원번호
   *  @return true or false
   *  
   *  @details 외래키 값과 회원번호를 통해 임시파일을 정상파일로 변경
   *  회원번호를 넣는 이유는 자신이 올린 파일만 업데이트 하기위함
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
   */

  /**
   *  @brief 파일설명 업데이트
   *  
   *  @param [in] $key   파일키값 file_no
   *  @param [in] $desc  파일에 대한 설명
   *  @param [in] $mb_no 회원번호 (회원번호로 권한 검사할 경우)
   *  @return true or false
   *  
   *  @details Details
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
   */

}
