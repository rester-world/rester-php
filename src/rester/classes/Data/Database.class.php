<?php
namespace Rester\Data;
use cfg;
use Exception;
use \PDO;
/**
 * Class Database
 */
class Database extends PDO
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var string table name
     */
    private $tbn;

    /**
     * Database constructor.
     *
     * @param string $dsn
     * @param string $user_name
     * @param string $password
     *
     * @throws Exception
     */
    public function __construct($dsn, $user_name, $password)
    {
        try
        {
            $this->schema = null;
            $this->tbn = null;
            parent::__construct($dsn, $user_name, $password);

            $timezone = cfg::Get('default','timezone');
            $this->exec("set names utf8; SET time_zone='{$timezone}';");
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * Set Schema
     * 1. 파일위치
     * 2. object 직접
     * 3. 모듈의 table.??.ini 파일
     *
     * @param object $schema
     *
     * @return bool
     * @throws Exception
     */
    public function set_schema($schema=null)
    {
        // 파일위치로 생성
        if(is_file($schema))
        {
            try
            {
                $this->schema = new Schema($schema);
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
        elseif(is_object($schema))
        {
            $this->schema = $schema;
        }
        elseif(is_file($path = \rester::path_schema($schema)))
        {
            try
            {
                $this->schema = new Schema($path);
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
        else
        {
            throw new Exception("지원되는 파라미터가 아닙니다.");
        }
        return true;
    }

    /**
     * @param string $table_name
     *
     * @throws Exception
     */
    public function set_table($table_name)
    {
        if(!is_string($table_name)) throw new Exception("테이블 이름을 입력하세요.");
        $this->tbn = $table_name;
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return bool|\PDOStatement
     * @throws Exception
     */
    private function common_query($query, $data = array())
    {
        if(!is_object($this->schema)) $this->set_schema();
        if (!is_string($query)) throw new Exception("1번째 파라미터는 문자열입니다.");
        if(!($stmt = $this->prepare($query))) throw new Exception("DB 객체가 생성되지 않았습니다.");

        try
        {
            $data = $this->schema->validate($data,true);
            foreach ($data as $key => &$value) $stmt->bindParam($key, $value);
            $stmt->execute();
        }
        catch (Exception $e)
        {
            throw $e;
        }

        return $stmt;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws Exception
     */
    public function insert($data)
    {
        if ($this->tbn===null) throw new Exception("테이블 이름을 설정해야 합니다.");

        list($fields, $values, $data) = $this->extract_data($data);
        $fields = implode(',',$fields);
        $values = implode(',',$values);
        $query =  "INSERT INTO {$this->tbn} ({$fields}) VALUES ({$values})";

        try
        {
            $this->common_query($query, $data);
            return $this->lastInsertId();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 쿼리생성용 데이터 뽑기
     * 필드명에는 ` 문자를 씌워준다.
     *
     * @param array $data
     *
     * @return array
     */
    protected function extract_data($data)
    {
        $fields = $values = $_data = array();
        foreach ($data as $k=>$v)
        {
            if(strpos($k, ':')===0)
            {
                $fields[] = '`'.substr($k, 1).'`';
                $values[] = $k;
                $_data[substr($k, 1)] = $v;
            }
            else
            {
                $fields[] = '`'.$k.'`';
                $values[] = ':'.$k;
                $_data[$k] = $v;
            }
        }
        return array($fields, $values, $_data);
    }

    /**
     * @param string $query
     *
     * @return array
     * @throws Exception
     */
    public function select($query)
    {
        try
        {
            $stmt = $this->common_query($query);
            return $stmt->fetchAll();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $limit
     *
     * @return array
     * @throws Exception
     */
    public function simple_select($key,$value,$limit=0)
    {
        $query = "SELECT * FROM `$this->tbn` WHERE {$key}=:{$key} ";
        if($limit) $query .= " LIMIT {$limit} ";

        try
        {
            $stmt = $this->common_query($query,array($key=>$value));
            return $stmt->fetchAll();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $key
     * @param string|integer $value
     * @param string $key2
     * @param string|integer $value2
     * @param int $limit
     *
     * @return array
     * @throws Exception
     */
    public function simple_select_2con($key,$value,$key2,$value2,$limit=0)
    {
        $query = "SELECT * FROM `$this->tbn` WHERE {$key}=:{$key} AND {$key2}=:{$key2} ";
        if($limit) $query .= " LIMIT {$limit} ";

        try
        {
            $stmt = $this->common_query($query,array($key=>$value,$key2=>$value2));
            return $stmt->fetchAll();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * example
     * simple_select_ex("file_owner=:file_owner AND file_tmp=:file_tmp LIMIT 10", $owner, 1)
     *
     * @param string $where
     *
     * @return array
     * @throws Exception
     */
    public function simple_select_ex($where)
    {
        $arg = array_slice(func_get_args(),1);
        $query = "SELECT * FROM `$this->tbn` WHERE {$where} ";

        preg_match_all('/:([a-zA-z0-9-_]+)/', $where, $matches);

        $data = array();
        foreach ($matches[1] as $k=>$field)
        {
            $data[$field] = $arg[$k];
        }

        try
        {
            $stmt = $this->common_query($query,$data);
            return $stmt->fetchAll();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $query
     *
     * @return mixed
     * @throws Exception
     */
    public function fetch($query)
    {
        try
        {
            return $this->select($query)[0];
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param  string $query
     *
     * @return int
     * @throws Exception
     */
    public function delete($query)
    {
        try
        {
            $stmt = $this->common_query($query);
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return int
     * @throws Exception
     */
    public function simple_delete($key,$value)
    {
        $query = "DELETE FROM `$this->tbn` WHERE {$key}=:{$key} LIMIT 1 ";

        try
        {
            $stmt = $this->common_query($query, array($key=>$value));
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return int
     * @throws Exception
     */
    public function update($query, $data=[])
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 1개의 레코드를 업데이트 함
     *
     * @param array $data
     *
     * @param string $where_key
     * @param string $where_value
     *
     * @return int
     * @throws Exception
     */
    public function simple_update($data, $where_key, $where_value)
    {
        if ($this->tbn===null) throw new Exception("테이블 이름을 설정해야 합니다.");

        $query_set = array();
        list($fields, $values, $data) = $this->extract_data($data);

        foreach ($fields as $k=>$v)
        {
            $query_set[] = $v.'='.$values[$k];
        }
        $query_set = implode(',',$query_set);

        $query = " UPDATE `{$this->tbn}` SET {$query_set} WHERE `{$where_key}` = '{$where_value}' LIMIT 1 ";

        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }



    /**
     * @param $key
     *
     * @return mixed
     * @throws Exception
     */
    public function get_password($key)
    {
        try
        {
            return $this->query('select password("' . $key . '") as pw')->fetch()['pw'];
        }
        catch (Exception $e)
        {
            throw $e;

        }
    }

}