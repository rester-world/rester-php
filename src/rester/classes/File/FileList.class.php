<?php
namespace Rester\File;
use Exception;
use Rester\Exception\RequireModuleName;

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
     * @throws Exception
     */
    public function __construct()
    {
    }

    /**
     * @param integer $fkey
     *
     * @return File[]
     * @throws Exception
     */
    public function fkey($fkey)
    {
        $result = array();
        try
        {
            foreach ($this->db->simple_select(self::field_fkey, $fkey) as $row)
            {
                $result[] = new File($row);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
        return $result;
    }

    /**
     * @param integer $owner
     *
     * @return File[]
     * @throws Exception
     */
    public function owner($owner)
    {
        $result = array();
        try
        {
            foreach ($this->db->simple_select(self::field_owner, $owner) as $row)
            {
                $result[] = new File($row);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
        return $result;
    }

    /**
     * @param integer $fkey
     * @param integer $owner
     *
     * @return File[]
     * @throws Exception
     */
    public function fkey_owner($fkey, $owner)
    {
        $result = array();
        try
        {
            foreach ($this->db->simple_select_2con(self::field_fkey, $fkey, self::field_owner, $owner) as $row)
            {
                $result[] = new File($row);
            }
        }
        catch (Exception $e)
        {
            throw $e;
        }
        return $result;
    }

    /**
     * 내가 업로드한 파일중 tmp 파일
     *
     * @param integer $owner
     *
     * @return File[]
     * @throws Exception
     */
    public function tmp($owner)
    {
        $result = array();
        foreach($this->db->simple_select_2con(self::field_owner,$owner, self::field_tmp, 1) as $row)
        {
            $result[] = new File($row);
        }
        return $result;
    }

}
