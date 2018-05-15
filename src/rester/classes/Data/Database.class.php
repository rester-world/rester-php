<?php
namespace Rester\Data;
/**
 * Class Database
 */
class Database extends \PDO
{
    /**
     * @var object Schema class object
     */
    private $schema;

    /**
     * @var string table name
     */
    private $tbn;

    /**
     * Database constructor.
     *
     * @param $dsn
     * @param $user_name
     * @param $password
     *
     * @throws \Exception
     */
    public function __construct($dsn, $user_name, $password)
    {
        try
        {
            $this->schema = null;
            $this->tbn = null;
            parent::__construct($dsn, $user_name, $password);
            $this->exec("set names utf8");
        }
        catch (\Exception $e)
        {
            throw new \Exception('DB Connect Fail');
        }
    }

    /**
     * @param object $schema
     */
    public function set_schema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param string $table_name
     */
    public function set_table($table_name)
    {
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
        if (!is_string($query)) throw new \Exception("1번째 파라미터는 문자열입니다.");
        if(!($stmt = $this->prepare($query))) throw new \Exception("DB 객체가 생성되지 않았습니다.");

        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        foreach ($data as $key => &$value) $stmt->bindParam($key, $value);
        if (!$stmt->execute()) throw new \Exception('쿼리 실패 입니다.');
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
        try
        {
            $query = $this->gen_insert_query($data);
            $this->common_query($query, $data);
            return $this->lastInsertId();
        }
        catch (\Exception $e)
        {
            throw $e;//new \Exception('Insert Error');
        }
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function gen_insert_query($data)
    {
        $fields = $values = array_keys($data);
        array_walk($fields, function(&$item) { if(strpos($item, ':')===0) $item = substr($item, 1); });
        $fields = implode(',',$fields);
        $values = implode(',',$values);
        return "INSERT INTO {$this->tbn} ({$fields}) VALUES ({$values})";
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
    public function update($query, $data = array())
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
            return $this->query($query)->fetch(\PDO::FETCH_ASSOC);
        }
        catch (\Exception $e)
        {
            throw new \Exception('Fetch Error');

        }
    }

    /**
     * @param $query
     *
     * @return array
     * @throws \Exception
     */
    public function fetch_array($query)
    {
        try
        {
            return $this->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch (\Exception $e)
        {
            throw new \Exception('Fetch Array Error');

        }

    }

    /**
     * @param $query
     *
     * @return array
     * @throws \Exception
     */
    public function fetch_object($query)
    {
        try
        {
            return $this->query($query)->fetchAll(\PDO::FETCH_OBJ);
        }
        catch (\Exception $e)
        {
            throw new \Exception('Fetch Object Error');

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
            throw new \Exception('Password Error');

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
            throw new \Exception('Affected Row Error');

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