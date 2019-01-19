<?php

/**
 * Class rester
 * kevinpark@webace.co.kr
 *
 * 기본 핵심 모듈
 */
class rester
{
    const path_module = 'modules';
    const file_verify_func = 'verify.php';
    const file_verify = 'verify.ini';
    const file_config = 'config.ini';

    protected static $request_param = [];
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = [];
    protected static $warning = [];
    protected static $error = [];

    protected static $cfg;
    protected static $check_auth = false;
    protected static $use_cache = false;
    protected static $use_cache_header = false;
    protected static $cache_timeout;
    protected static $header;

    /**
     * response code
     * execute header();
     */
    public static function run_headers()
    {
        http_response_code(self::$response_code);
        header("Content-type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
    }

    /**
     * @param string $v
     */
    public static function set_mime($v) { self::$header = $v; }

    /**
     * @param $v
     */
    public static function set_header($v) { self::$header = $v; }

    /**
     * @param string $module
     * @param string $proc
     * @param array  $param
     *
     * @return bool|array
     */
    public static function sql($module, $proc, $param=[])
    {

        try
        {
            $cfg = cfg::Get('sql');
            if(!$cfg['host'] || !$cfg['port']) throw new Exception("There is no config.(sql)");

            $url = implode('/',array(
                $cfg['host'].':'.$cfg['port'],
                'v1',
                $module,
                $proc
            ));

            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param),
            ));

            $response_body = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($response_body,true);
            if(!$res['success'])
            {
                rester::failure();
                rester::error(implode('<br/>',$res['error']));
            }
            return $res['data'];
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::error($e->getMessage());
            return false;
        }
    }

    /**
     * verify request parameter
     * check body | query string
     *
     * @throws Exception
     */
    protected static function check_parameter()
    {
        if($path_verify = self::path_verify())
        {
            self::reset_parameter();
            $schema = new schema($path_verify);
            try
            {
                if($data = $schema->validate(cfg::parameter()))
                    foreach($data as $k => $v) rester::set_request_param($k, $v);
            }
            catch (Exception $e)
            {
                throw new Exception("rester::check_parameter() - ".$e->getMessage());
            }
        }
    }

    /**
     * run rester
     *
     * @throws Exception
     */
    public static function run()
    {
        $module = cfg::module();
        $proc = cfg::proc();
        $method = cfg::request_method();
        $response_data = null;

        // ---------------------------------------------------------------------
        /// include verify function
        // ---------------------------------------------------------------------
        if($path_verify_func = self::path_verify_func())
        {
            include $path_verify_func;
        }

        // parameter
        self::check_parameter();

        // ---------------------------------------------------------------------
        /// check file
        // ---------------------------------------------------------------------
        $path_proc = self::path_proc();
        if(false === $path_proc)
        {
            throw new Exception("Not found procedure. Module: {$module}, Procedure: {$proc} ");
        }

        // ---------------------------------------------------------------------
        /// check auth
        // ---------------------------------------------------------------------
        if(self::$check_auth)
        {
            session::get(cfg::token());
        }

        // ---------------------------------------------------------------------
        /// include config.ini
        // ---------------------------------------------------------------------
        $cfg = [];
        if($path = self::path_cfg())
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        }
        self::$cfg = $cfg;

        // ---------------------------------------------------------------------
        /// check cache
        // ---------------------------------------------------------------------
        if(self::$use_cache)
        {
            $redis_cfg = cfg::cache();
            if(!($redis_cfg['host'] && $redis_cfg['port']))
                throw new Exception("Require cache config to use cache.");

            $redis = new Redis();
            $cache_key = implode('_', array_merge(array($module,$proc,$method),self::param()));
            if($redis->connect($redis_cfg['host'], $redis_cfg['port'], 2000))
            {
                if($redis_cfg['auth'])
                {
                    if(!$redis->auth($redis_cfg['auth']))
                    {
                        throw new Exception("Can not authenticate redis server. Check the config [auth].");
                    }
                }
                // get cached data
                $response_data = $redis->get($cache_key);

                // 캐시된 데이터가 배열일 경우
                $_d = @json_decode($response_data,true);
                if(is_array($_d)) $response_data = $_d;

                // 캐시된 데이터가 없을 경우
                // 프로시저를 인크루드하고 캐쉬서버에 저장함
                if(!$response_data)
                {
                    $__cache = $response_data = include $path_proc;
                    // 결과가 배열일 경우 encode 함
                    if(is_array($__cache)) $__cache = json_encode($__cache);
                    $redis->set($cache_key,$__cache,self::$cache_timeout);
                }
            }
            else
            {
                // redis 접속불가
                throw new Exception("Can not access redis server. Check the config [host and port].");
            }
            // close redis
            $redis->close();
        }
        else
        {
            $response_data = include $path_proc;
        }

        // return json body
        return $response_data;
    }

    /**
     * @param string $module
     * @param string $proc
     * @param array  $query
     *
     * @return mixed
     * @throws Exception
     */
    public static function call_module($module, $proc, $query=[])
    {
        $old_module = cfg::change_module($module);
        $old_proc = cfg::change_proc($proc);

        $res = false;

        try
        {
            $_POST = $query;
            cfg::init_parameter();
            self::check_parameter();

            if($path = self::path_proc())
            {
                $res = include $path;
            }
            else
            {
                self::failure();
                self::error("Can not found module: {$module}");
            }
        }
        catch (Exception $e)
        {
            self::failure();
            self::error($e->getMessage());
        }

        cfg::change_proc($old_proc);
        cfg::change_module($old_module);
        return $res;
    }

    /**
     * @param string $proc
     * @param array  $query
     *
     * @return mixed
     * @throws Exception
     */
    public static function call_proc($proc, $query=[])
    {
        $old_proc = cfg::change_proc($proc);

        $res = false;

        try
        {
            $_POST = $query;
            cfg::init_parameter();
            self::check_parameter();

            if($path = self::path_proc())
            {
                $res = include $path;
            }
            else
            {
                self::failure();
                self::error("Can not found procedure: {$proc}");
            }
        }
        catch (Exception $e)
        {
            self::failure();
            self::error($e->getMessage());
        }

        cfg::change_proc($old_proc);
        return $res;
    }

    /**
     * @param string $proc
     * @param array $query
     * @return string|bool
     */
    public static function url_proc($proc, $query=[])
    {
        if(!$proc) return false;
        $http_host = cfg::Get('default','http_host');
        $module = cfg::module();
        $_query = [];
        foreach ($query as $k=>$v) { $_query[] = $k.'='.$v; }
        $_query = trim(implode('&',$_query));
        $_query = $_query?'?'.$_query:'';
        return  $http_host."/v1/{$module}/{$proc}{$_query}";
    }

    /**
     * @param string $module
     * @param string $proc
     * @param array $query
     * @return bool|string
     */
    public static function url_module($module, $proc, $query=[])
    {
        if(!$module || !$proc) return false;
        $http_host = cfg::Get('default','http_host');
        $_query = [];
        foreach ($query as $k=>$v) { $_query[] = $k.'='.$v; }
        $_query = trim(implode('&',$_query));
        $_query = $_query?'?'.$_query:'';
        return  $http_host."/v1/{$module}/{$proc}{$_query}";
    }

    /**
     * Path module
     *
     * @return string
     */
    protected static function path_module() { return dirname(__FILE__).'/../../'.self::path_module; }

    /**
     * Path to procedure file
     *
     * @param null|string $module_name
     * @param null|string $proc_name
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_proc($module_name = null, $proc_name = null)
    {
        if($timeout = intval(cfg::Get('cache','timeout'))) self::$cache_timeout = $timeout;
        if(null === $module_name) $module_name = cfg::module();
        if(null === $proc_name) $proc_name = cfg::proc();

        $method = strtolower(cfg::Get('method'));
        $path_array = array(
            self::path_module(),
            $module_name,
            $proc_name
        );

        $path = false;
        foreach (glob(implode('/',$path_array).'/'.$method.'*.php') as $filename)
        {
            if(strpos($filename,self::file_verify_func)!==false) continue;

            $path = $filename;
            $filename_arr = explode('.',$filename);
            if(in_array('auth',$filename_arr)) { self::$check_auth = true; }
            array_walk($filename_arr, function($item){
                if(strpos($item,'cache')!==false)
                {
                    self::$use_cache = true;
                    $cache_arr = explode('_',$item);
                    if(
                        in_array('img', $cache_arr) ||
                        in_array('image', $cache_arr) ||
                        in_array('file', $cache_arr))
                        self::$use_cache_header = true;
                    foreach($cache_arr as $arg)
                    {
                        if($timeout = intval($arg))
                        {
                            self::$cache_timeout = $timeout;
                            break;
                        }
                    }
                }
            });
            break;
        }
        return $path;
    }

    /**
     * Path to fn file
     *
     * @param string      $name
     *
     * @return bool|string
     * @throws Exception
     */
    public static function path_fn($name)
    {
        $module_name = cfg::module();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            'fn.'.$name.'.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to config file
     *
     * @return bool|string
     * @throws Exception
     */
    public static function path_cfg()
    {
        $path = implode('/',array(
            self::path_module(),
            cfg::module(),
            self::file_config
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_verify()
    {
        $module_name = cfg::module();
        $proc_name = cfg::proc();
        $method = cfg::request_method();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            $method.'.'.self::file_verify
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_verify_func()
    {
        $module_name = cfg::module();
        $proc_name = cfg::proc();
        $method = cfg::request_method();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            $method.'.'.self::file_verify_func
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * 요청바디 설정
     *
     * @param string $key
     * @param string $value
     */
    public static function set_request_param($key, $value) { self::$request_param[$key] = $value; }
    public static function reset_parameter() { self::$request_param = []; }

    /**
     * 요청값 반환
     * @param null|string $key
     * @return bool|mixed
     */
    public static function param($key=null)
    {
        if(isset(self::$request_param[$key])) return self::$request_param[$key];
        if($key == null) return self::$request_param;
        return false;
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return string|array
     */
    public static function cfg($section='', $key='')
    {
        if($section==='') return self::$cfg;
        if($section && $key) return self::$cfg[$section][$key];
        return self::$cfg[$section];
    }

    /**
     * @param integer $code
     */
    public static function set_response_code($code) { self::$response_code = $code; }

    /**
     * Add message
     *
     * @param null|string $msg
     *
     * @return array
     */
    public static function msg($msg=null)
    {
        if($msg===null) return self::$msg;
        else self::$msg[] = $msg;
        return null;
    }

    /**
     * Add warning message
     *
     * @param null|string $msg
     *
     * @return array
     */
    public static function warning($msg=null)
    {
        if($msg===null) return self::$warning;
        else self::$warning[] = $msg;
        return null;
    }

    /**
     * Add error
     *
     * @param null|string $msg
     *
     * @return array
     */
    public static function error($msg=null)
    {
        if($msg===null) return self::$error;
        else self::$error[] = $msg;
        return null;
    }

    /**
     * set failure
     */
    public static function failure() { self::$success = false; }

    /**
     * @return bool
     */
    public static function isSuccess() { return self::$success; }
}
