<?php
use \Rester\Data\Database;

/**
 * Class db
 */
class db
{
    /**
     * @var array 데이터베이스 인스턴스
     */
    private static $inst = array();

    const config_file = "dbconfig.php"; // config 파일명

    /**
     * @param $config_name
     *
     * @return mixed
     * @throws Exception
     */
    public static function get($config_name)
    {
        try
        {
            $cfg = include dirname(__FILE__).'/../../../cfg/'.self::config_file;
            $cfg = $cfg[$config_name];

            if (!is_array($cfg)) throw new Exception('해당 DB 정보가 없습니다.');
            if (!$cfg['db_name']) throw new Exception('해당 DB 이름이 없습니다.');

            if (self::$inst[$cfg['db_name']] == null)
            {
                $dsn = self::create_dsn($cfg);
                self::$inst[$cfg['db_name']] = new Database($dsn, $cfg['user'], $cfg['password']);
            }
        }
        catch (Exception $e)
        {
            echo $e;
            exit;
        }
        return self::$inst[$cfg['db_name']];
    }

    /**
     * @param string $arg
     *
     * @return string
     * @throws Exception
     */
    private static function create_dsn($arg)
    {
        $db_type = strtolower($arg['type']);

        if (!is_string($db_type)) throw new Exception('커넥션 정보가 명확하지 않습니다.');

        if ($db_type == "oracle" || $db_type == "orcl" || $db_type == "oci")
        {
            $dns = "oci:dbname=//" . $arg['host'] . ':' . $arg['port'] . '/' . $arg['db_name'];
        }
        elseif ($db_type == "mssql" || $db_type == "dblib")
        {
            $dns = "dblib:host=" . $arg['host'] . ':' . $arg['port'] . ';dbname=' . $arg['db_name'];
        }
        else
        {
            $dns = $db_type . ":host=" . $arg['host'] . ";port=" . $arg['port'] . ";dbname=" . $arg['db_name'];
        }
        return $dns;
    }
}
