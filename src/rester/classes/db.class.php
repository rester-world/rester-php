<?php
use \Rester\Data\Database;

/**
 * Class db
 * @author kevinpark@webace.co.kr
 * @author
 */
class db
{
    /**
     * @var array 데이터베이스 인스턴스
     */
    private static $inst = array();

    const config_file = "dbconfig.php"; // config 파일명

    /**
     * @param string $config_name
     *
     * @return \Rester\Data\Database
     * @throws Exception
     */
    public static function get($config_name='default')
    {
        if(!is_string($config_name)) throw new Exception("1번째 파라미터는 dbconfig.php 파일 설정 키 값 이어야 합니다.");

        // 처음 호출이면 아래 내용 실행
        if (self::$inst[$config_name] == null)
        {
            $cfg = include dirname(__FILE__).'/../../../cfg/'.self::config_file;

            if(!isset($cfg[$config_name])) throw new Exception("키 값에 맞는 설정이 없습니다.");

            $cfg = $cfg[$config_name];
            if (
                !isset($cfg['type'])
                || !isset($cfg['host'])
                || !isset($cfg['user'])
                || !isset($cfg['password'])
                || !isset($cfg['database'])
            )
            {
                throw new Exception('dbconfig.php 설정 형식이 잘못되었습니다.');
            }

            try
            {
                $dsn = self::create_dsn($cfg);
                self::$inst[$config_name] = new Database($dsn, $cfg['user'], $cfg['password']);
            }
            catch (Exception $e)
            {
                echo $e;
                exit;
            }
        }

        return self::$inst[$config_name];
    }

    /**
     * @param array $arg
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
            $dns = "oci:dbname=//" . $arg['host'] . ':' . $arg['port'] . '/' . $arg['database'];
        }
        elseif ($db_type == "mssql" || $db_type == "dblib")
        {
            $dns = "dblib:host=" . $arg['host'] . ':' . $arg['port'] . ';dbname=' . $arg['database'];
        }
        else
        {
            $dns = $db_type . ":host=" . $arg['host'] . ";port=" . $arg['port'] . ";dbname=" . $arg['database'];
        }
        return $dns;
    }
}
