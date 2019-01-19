<?php
class cfg
{
    const query_version = 'v';
    const query_module = 'm';
    const query_proc = 'proc';

    private static $name = 'rester.ini';  // config file name
    private static $data;	// config data

    /**
     * @var array default configuration
     */
    protected static $default = array(
        'default'=>array(
            'debug_mode'=>false,
            'timezone'=>'Asia/Seoul'
        ),
        'access_control'=>array(
            'allows_origin'=>'*'
        ),
    );

    /**
     * @return string
     */
    public static function module() { return self::Get('module'); }

    /**
     * @param string $module
     * @return string
     */
    public static function change_module($module)
    {
        $old = self::module();
        self::$data['module'] = $module;
        return $old;
    }

    /**
     * @return string
     */
    public static function proc() { return self::Get('proc'); }

    /**
     * @param string $proc
     * @return string
     */
    public static function change_proc($proc)
    {
        $old = self::proc();
        self::$data['proc'] = $proc;
        return $old;
    }

    /**
     * @return string
     */
    public static function request_method() { return strtolower(self::Get('method')); }

    /**
     * @return array
     */
    public static function parameter() { return self::Get('request-body'); }

    /**
     * @return string
     */
    public static function token() { return self::Get('request-body','token'); }

    /**
     * @return array
     */
    public static function cache() { return self::Get('cache'); }

    /**
     * extract body parameter from json body, POST and GET
     */
    public static function init_parameter()
    {
        // Extract request parameter
        // Json, POST, GET
        self::$data['request-body'] = array();
        if($body = json_decode(file_get_contents('php://input'),true))
        {
            self::$data['request-body'] = $body;
        }
        else
        {
            self::$data['request-body'] = $_POST;
            unset($_POST);
        }

        foreach ($_GET as $k=>$v)
        {
            if(!isset(self::$data['request-body'][$k])) self::$data['request-body'][$k] = $v;
        }
        unset($_GET);
    }

    /**
     * 기본정보 초기화
     *
     * @throws Exception
     */
    public static function init()
    {
        //--------------------------------------------------------------------------------------
        /// Load config (rester.ini)
        //--------------------------------------------------------------------------------------
        $path = dirname(__FILE__).'/../../../cfg/'.self::$name;
        if(is_file($path))
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        else
            throw new Exception("There is no config file.(rester.ini)");

        //--------------------------------------------------------------------------------------
        /// Set default value
        //--------------------------------------------------------------------------------------
        foreach (self::$default as $k=>$v)
        {
            foreach ($v as $kk => $vv) { if (!isset($cfg[$k][$kk])) $cfg[$k][$kk] = $vv; }
        }

        //--------------------------------------------------------------------------------------
        /// 오류출력설정
        //--------------------------------------------------------------------------------------
        if($cfg['default']['debug_mode'])
            error_reporting(E_ALL ^ (E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED));
        else
            error_reporting(0);

        //--------------------------------------------------------------------------------------
        /// timezone 설정
        /// rester.ini
        //--------------------------------------------------------------------------------------
        date_default_timezone_set($cfg['default']['timezone']);

        //--------------------------------------------------------------------------------------
        /// Check method
        /// Allowed post and get
        //--------------------------------------------------------------------------------------
        if($_SERVER['REQUEST_METHOD']=='POST' || $_SERVER['REQUEST_METHOD']=='GET')
            $cfg['method'] = $_SERVER['REQUEST_METHOD'];
        else
            throw new Exception("Invalid request METHOD.(Allowed POST,GET)");

        //--------------------------------------------------------------------------------------
        /// Extract access control
        //--------------------------------------------------------------------------------------
        if($cfg['access_control']['allows_origin']!='*')
            $cfg['access_control']['allows_origin'] = explode(',', $cfg['access_control']['allows_origin']);
        array_walk_recursive($cfg, function(&$v) { $v = trim($v); });

        //--------------------------------------------------------------------------------------
        /// extract version
        //--------------------------------------------------------------------------------------
        if(preg_match('/^[0-9][0-9.]*$/i',$_GET[self::query_version],$matches))
        {
            $cfg['version'] = $matches[0];
        }
        else
        {
            if($_GET[self::query_version]=='')
                throw new Exception("Access denied.(root directory)");
            else
                throw new Exception("Invalid version name.");
        }

        //--------------------------------------------------------------------------------------
        /// Check module name
        //--------------------------------------------------------------------------------------
        if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[self::query_module]),$matches))
            $cfg['module'] = $matches[0];
        else
            throw new Exception("Invalid module name.");

        //--------------------------------------------------------------------------------------
        /// Check procedure name
        //--------------------------------------------------------------------------------------
        if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[self::query_proc]),$matches))
            $cfg['proc'] = $matches[0];
        else
            throw new Exception("Invalid procedure name.");

        //--------------------------------------------------------------------------------------
        /// Check allows ip address
        /// Check ip from share internet
        //--------------------------------------------------------------------------------------
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $access_ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        //to check ip is pass from proxy
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $access_ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $access_ip=$_SERVER['REMOTE_ADDR'];
        }

        if($cfg['access_control']['allows_origin']!='*')
        {
            if(!is_array($cfg['access_control']['allows_origin'])) $cfg['access_control']['allows_origin'] = array($cfg['access_control']['allows_origin']);
            if(!in_array($access_ip,$cfg['access_control']['allows_origin'])) throw new Exception("Access denied.(Not allowed ip address:{$access_ip})");
        }

        self::$data = $cfg;

        self::init_parameter();
    }

    /**
     * return config
     *
     * @param string $section
     * @param string $key
     *
     * @return array|string
     */
    public static function Get($section='', $key='')
    {
        if($section==='') return self::$data;
        if($section && $key) return self::$data[$section][$key];
        return self::$data[$section];
    }
}
