<?php
namespace Rester\File;

/**
 * Class fileList
 * kevinpark@webace.co.kr
 *
 * 업로드된 파일 목록 관련 클래스
 */
class FileList extends File
{

    /**
     * FileList constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
    }

    public function get_by_fkey($fkey)
    {
        //$result = $this->db->simple_select(self::field_fkey, $fkey);
        // return number of fileDB instance;
    }

    public function get_by_owner()
    {
        //$pdo->sql_query_list('select ');
        // return number of fileDB instance;
    }

    public function get_tmp($owner)
    {
        //$pdo->sql_query_list('select ');
    }


  /**
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


}
