<?php
class db extends PDO
{


    /**
     * @var array
     */
    private static $inst_arr = array();
    const db_file = "db_config.php";

    /**
     * db constructor.
     *
     * @param $arg
     *
     * @throws Exception
     */
    public function __construct($arg)
    {
        try
        {
            include_once(dirname(dirname(__FILE__).'/../../../cfg/'.self::db_file));

            $info = $this->connect_info($arg);
            parent::__construct($info, $arg[user_name], $arg[password]);
            $this->set_utf8();
        }
        catch (Exception $e)
        {
            throw new Exception('DB Connect Fail');
        }
    }

    /**
     * @param string $arg
     *
     * @return string
     * @throws Exception
     */
    private function connect_info($arg)
    {
        $db_type = strtolower($arg['db_type']);
        if (!is_string($db_type)) throw new Exception('커넥션 정보가 명확하지 않습니다.');
        if ($db_type == "oracle" || $db_type == "orcl" || $db_type == "oci")
        {
            $dns = "oci:dbname=//" . $arg['host'] . ':' . $arg['port'] . '/' . $arg[db_name];
        }
        elseif ($db_type == "mssql" || $db_type == "dblib")
        {
            $dns = "dblib:host=" . $arg['host'] . ':' . $arg['port'] . ';dbname=' . $arg[db_name];
        }
        else
        {
            $dns = $db_type . ":host=" . $arg['host'] . ";port=" . $arg['port'] . ";dbname=" . $arg[db_name];
        }
        return $dns;
    }

    /**
     * @param $cnf
     *
     * @return mixed
     * @throws Exception
     */
    public static function get_con($cnf)
    {
        try
        {
            include_once(dirname(__FILE__).'/../../../cfg/'.self::db_file);
            $arg = $db_config[$cnf];
            if (!is_array($arg))
            {
                throw new Exception('해당 DB 정보가 없습니다.<br/>');
            }

            $db_name = $arg['db_name'];

            if (!$db_name) throw new Exception('해당 DB 이름이 없습니다.<br/>');

            if (self::$inst_arr[$db_name] == null)
            {
                self::$inst_arr[$db_name] = new db($arg);
            }
            if (!self::$inst_arr[$db_name]) throw new Exception('인스턴스 에러 <br/>');
            return self::$inst_arr[$db_name];
        }
        catch (Exception $e)
        {
            throw new Exception('Instance Error');
        }
    }


    /**
     * utf-8 setting
     */
    private function set_utf8()
    {
        $this->exec("set names utf8");
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return bool|PDOStatement
     * @throws Exception
     */
    private function common_query($query, $data = array())
    {
        if (!is_string($query)) throw new Exception("1번째 파라미터는 문자열입니다. <br/>");
        $stmt = $this->prepare($query);
        if(!$stmt) throw new Exception("DB 객체가 생성되지 않았습니다. <br/>");
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($data as $key => &$value)
            $stmt->bindParam($key, $value);
        if (!$stmt->execute()) throw new Exception('쿼리 실패 입니다. <br/>');
        return $stmt;
    }


    /**
     * @param string $query
     * @param array  $data
     *
     * @return int
     * @throws Exception
     */
    public function insert($query, $data = array())
    {
        try
        {
            $this->common_query($query, $data);
        }
        catch (Exception $e)
        {
            throw new Exception('Insert Error');
        }

        return $this->last_insert_id();
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return array
     * @throws Exception
     */
    public function select($query, $data = array())
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->fetchAll();
        }
        catch (Exception $e)
        {
            throw new Exception('Select Error');
        }
    }

    /**
     * @param string $query
     * @param array  $data
     *
     * @return int
     * @throws Exception
     */
    public function update($query, $data = array())
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw new Exception('Update Error');
        }
    }

    /**
     * @param       $query
     * @param array $data
     *
     * @return int
     * @throws Exception
     */
    public function delete($query, $data = array())
    {
        try
        {
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw new Exception('Delete Error');
        }
    }

    /**
     * @param $query
     *
     * @return mixed
     * @throws Exception
     */
    public function fetch($query)
    {
        try
        {
            return $this->query($query)->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e)
        {
            throw new Exception('Fetch Error');

        }
    }

    /**
     * @param $query
     *
     * @return array
     * @throws Exception
     */
    public function fetch_array($query)
    {
        try
        {
            return $this->query($query)->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e)
        {
            throw new Exception('Fetch Array Error');

        }

    }

    /**
     * @param $query
     *
     * @return array
     * @throws Exception
     */
    public function fetch_object($query)
    {
        try
        {
            return $this->query($query)->fetchAll(PDO::FETCH_OBJ);
        }
        catch (Exception $e)
        {
            throw new Exception('Fetch Object Error');

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
            throw new Exception('Password Error');

        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function affected_row()
    {
        try
        {
            return $this->query('SELECT ROW_COUNT() as cnt;')->fetch()['cnt'];
        }
        catch (Exception $e)
        {
            throw new Exception('Affected Row Error');

        }

    }

    /**
     * @return string
     */
    public function last_insert_id()
    {
        return $this->lastInsertId();
    }

    /**
     * @param $table_name
     * @param $data
     *
     * @return string
     * @throws Exception
     */
    public function insert_ex($table_name, $data)
    {
        try
        {
            $query = $this->get_insert_string($table_name, $data);
            $this->common_query($query, $data);
            return $this->last_insert_id();
        }
        catch (Exception $e)
        {
            throw new Exception('Insert Ex Error');
        }
    }

    /**
     * @param $table_name
     * @param $data
     *
     * @return int
     * @throws Exception
     */
    public function delete_ex($table_name, $data)
    {
        try
        {
            $query = $this->get_delete_string($table_name, $data);
            $stmt = $this->common_query($query, $data);
            return $stmt->rowCount();
        }
        catch (Exception $e)
        {
            throw new Exception('Delete Ex Error');
        }
    }

    /**
     * @param $table_name
     * @param $data
     *
     * @return string
     */
    private function get_insert_string($table_name, $data)
    {
        $front_str = "insert into $table_name (";
        $back_str = " values(";
        foreach ($data as $key => $value)
        {
            if (substr($key, 0, 1) == ":") $key = substr($key, 1, strlen($key) - 1);
            $front_str .= $key . ",";
            $back_str .= ':' . $key . ",";
        }
        $front_str = substr($front_str, 0, -1) . ")";
        $back_str = substr($back_str, 0, -1) . ")";
        return $front_str . $back_str . ";";
    }

    private function get_delete_string($table_name, $data)
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