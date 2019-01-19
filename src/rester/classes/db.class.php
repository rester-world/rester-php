<?php
/**
 * Class db
 * PDO 인스턴스를 생성하고 관리함
 * 여러개의 데이터베이스를 연결할 수 있도록 디자인 됨
 * 사실상 rester-sql 을 사용하면 호출할 필요가 없는 클래스
 */
class db
{
    /**
     * @var array 데이터베이스 인스턴스
     */
    private static $inst = array();

    /**
     * @param string $config_name
     *
     * @return bool|PDO
     */
    public static function get($config_name='default')
    {
        try
        {
            if(!is_string($config_name)) throw new Exception("The parameter must be a string.");

            // 처음 호출이면 아래 내용 실행
            if (self::$inst[$config_name] == null)
            {
                $cfg = cfg::Get('database',$config_name);

                if(!$cfg) throw new Exception("There is no {$config_name} database setting.");
                if(!$cfg['type']) throw new Exception("There is no {$config_name}['type'] database setting.");
                if(!$cfg['host']) throw new Exception("There is no {$config_name}['host'] database setting.");
                if(!$cfg['user']) throw new Exception("There is no {$config_name}['user'] database setting.");
                if(!$cfg['password']) throw new Exception("There is no {$config_name}['password'] database setting.");
                if(!$cfg['database']) throw new Exception("There is no {$config_name}['database'] database setting.");

                $dsn = self::create_dsn($cfg);
                self::$inst[$config_name] = new PDO($dsn, $cfg['user'], $cfg['password']);
            }
            return self::$inst[$config_name];
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg($e->getMessage());
            return false;
        }
    }

    /**
     * @param array $db
     *
     * @return string
     * @throws Exception
     */
    private static function create_dsn($db)
    {
        $db_type = strtolower($db['type']);

        if ($db_type == "oracle" || $db_type == "orcl" || $db_type == "oci")
        {
            $dns = "oci:dbname=//" . $db['host'] . ':' . $db['port'] . '/' . $db['database'];
        }
        elseif ($db_type == "mssql" || $db_type == "dblib")
        {
            $dns = "dblib:host=" . $db['host'] . ':' . $db['port'] . ';dbname=' . $db['database'];
        }
        elseif($db_type == 'mysql')
        {
            $dns = $db_type . ":host=" . $db['host'] . ";port=" . $db['port'] . ";dbname=" . $db['database'];
        }
        else
        {
            throw new Exception("Database type({$db_type}) not supported.");
        }
        return $dns;
    }
}

