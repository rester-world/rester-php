<?php
namespace Rester\Data;
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
     * @throws \Exception
     */
    public function __construct($dsn, $user_name, $password)
    {
        //if(!is_string($dsn)) throw new \Rester\Exception\InvalidParamException("\$dsn : ", \Rester\Exception\InvalidParamException::REQUIRE_STRING);

        try
        {
            $this->schema = null;
            $this->tbn = null;
            parent::__construct($dsn, $user_name, $password);

            $this->exec("set names utf8");
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        catch (\Exception $e)
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
     * @throws \Exception
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
            catch (\Exception $e)
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
            catch (\Exception $e)
            {
                throw $e;
            }
        }
        else
        {
            throw new \Exception("지원되는 파라미터가 아닙니다.");
        }
        return true;
    }

    /**
     * @param string $table_name
     *
     * @throws \Exception
     */
    public function set_table($table_name)
    {
        if(!is_string($table_name)) throw new \Exception("테이블 이름을 입력하세요.");
        $this->tbn = $table_name;
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return bool|\PDOStatement
     * @throws \Exception
     */
    private function common_query($query, $data = array())
    {
        if(!is_object($this->schema)) $this->set_schema();
        if (!is_string($query)) throw new \Exception("1번째 파라미터는 문자열입니다.");
        if(!($stmt = $this->prepare($query))) throw new \Exception("DB 객체가 생성되지 않았습니다.");

        try
        {
            $data = $this->schema->validate($data);
            foreach ($data as $key => &$value) $stmt->bindParam($key, $value);
            if(!$stmt->execute()) throw new \Exception("쿼리 실행 실패");
        }
        catch (\Exception $e)
        {
            throw $e;
        }

        return $stmt;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws \Exception
     */
    public function insert($data)
    {
        if ($this->tbn===null) throw new \Exception("테이블 이름을 설정해야 합니다.");

        $fields = $values = array_keys($data);
        array_walk($fields, function(&$item) { if(strpos($item, ':')===0) $item = substr($item, 1); });
        $fields = implode(',',$fields);
        $values = implode(',',$values);
        $query =  "INSERT INTO {$this->tbn} ({$fields}) VALUES ({$values})";

        try
        {
            $this->common_query($query, $data);
            return $this->lastInsertId();
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return array
     * @throws \Exception
     */
    public function select($query, $data = array())
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->fetchAll();
        }
        catch (\Exception $e)
        {
            throw new \Exception('Select Error');
        }
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return int
     * @throws \Exception
     */
    public function update($query, $data)
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (\Exception $e)
        {
            throw new \Exception('Update Error');
        }
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return int
     * @throws \Exception
     */
    public function update_simple($data, $where_key, $where_value)
    {
        //

        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (\Exception $e)
        {
            throw new \Exception('Update Error');
        }
    }

    /**
     * @param       $query
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function delete($query, $data = array())
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (\Exception $e)
        {
            throw new \Exception('Delete Error');
        }
    }

    /**
     * @param $query
     *
     * @return mixed
     * @throws \Exception
     */
    public function fetch($query)
    {
        try
        {
            return $this->query($query)->fetch();
        }
        catch (\Exception $e)
        {
            throw new \Exception('Fetch Error');

        }
    }

    /**
     * @param $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function get_password($key)
    {
        try
        {
            return $this->query('select password("' . $key . '") as pw')->fetch()['pw'];
        }
        catch (\Exception $e)
        {
            throw $e;

        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function affected_row()
    {
        try
        {
            return $this->query('SELECT ROW_COUNT() as cnt;')->fetch()['cnt'];
        }
        catch (\Exception $e)
        {
            throw $e;

        }
    }

    /**
     * @param $table_name
     * @param $data
     *
     * @return int
     * @throws \Exception
     */
    public function delete_ex($table_name, $data)
    {
        try
        {
            $query = $this->gen_delete_query($table_name, $data);
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (\Exception $e)
        {
            throw new \Exception('Delete Ex Error');
        }
    }

    private function gen_delete_query($table_name, $data)
    {
        $str = "delete from $table_name where ";
        foreach ($data as $key => $value)
        {
            if (substr($key, 0, 1) == ":") $key = substr($key, 1, strlen($key) - 1);
            $str .= $key . "=:" . $key . " and ";
        }
        $str = substr($str, 0, -4);
        return $str;
    }

}