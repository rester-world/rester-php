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

    protected static $request_param = array();
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = array();

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
        if(self::$header) header("Content-type: ".self::$header);
        else header("Content-type: application/json; charset=UTF-8");
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
            if($res['success']) return $res['data'];
            throw new Exception($res['msg']);
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg($e->getMessage());
            return false;
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

        ///=====================================================================
        /// include verify function
        ///=====================================================================
        if($path_verify_func = self::path_verify_func())
        {
            include $path_verify_func;
        }

        ///=====================================================================
        /// check request parameter
        /// check body | query string
        ///=====================================================================
        if($path_verify = self::path_verify())
        {
            $schema = new schema($path_verify);
            try
            {
                if($data = $schema->validate(cfg::parameter()))
                    foreach($data as $k => $v) rester::set_request_param($k, $v);
            }
            catch (Exception $e)
            {
                throw new Exception("request-body | query: ".$e->getMessage());
            }
        }

        ///=====================================================================
        /// check file
        ///=====================================================================
        $path_proc = self::path_proc();
        if(false === $path_proc)
        {
            throw new Exception("Not found procedure. Module: {$module}, Procedure: {$proc} ");
        }

        ///=====================================================================
        /// check auth
        ///=====================================================================
        if(self::$check_auth) { session::get(cfg::token()); }

        ///=====================================================================
        /// check cache
        ///=====================================================================
        $redis_cfg = cfg::cache();
        if(self::$use_cache && !($redis_cfg['host'] && $redis_cfg['port'])) throw new Exception("Require cache config to use cache.");

        $response_data = null;
        $redis = new Redis();
        $cache_key = implode('_', array_merge(array($module,$proc,$method),self::param()));
        if(self::$use_cache)
        {
            $redis->connect($redis_cfg['host'], $redis_cfg['port']);
            if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

            // get cached data
            $response_data = $redis->get($cache_key);
            $_d = json_decode($response_data,true);
            if(is_array($_d)) $response_data = $_d;

            // get cached header
            if(self::$use_cache_header)
            {
                self::$header = $redis->get('header_'.$cache_key);
                $_header = json_decode(self::$header,true);
                if(is_array($_header)) self::$header = $_header;
            }
        }

        ///=====================================================================
        /// include config.ini
        ///=====================================================================
        $cfg = array();
        if($path = self::path_cfg())
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        }
        self::$cfg = $cfg;

        ///=====================================================================
        /// include procedure
        ///=====================================================================
        if(!$response_data) { $response_data = include $path_proc; }

        // cached header
        if(self::$use_cache_header && !$redis->get('header_'.$cache_key))
        {
            $_header = self::$header;
            if(is_array($_header)) $_header = json_encode($_header);
            $redis->set('header_'.$cache_key,$_header,self::$cache_timeout);
        }

        // cached body
        if(self::$use_cache && !$redis->get($cache_key))
        {
            $_d = $response_data;
            if(is_array($_d)) $_d = json_encode($_d);
            $redis->set($cache_key,$_d,self::$cache_timeout);
        }

        // close redis
        if(self::$use_cache) { $redis->close(); }

        ///=====================================================================
        /// print image or file
        ///=====================================================================
        if($mime = self::$header)
        {
            if(is_array($mime))
            {
                foreach($mime as $h) { header($h); }
            }
            else
            {
                header('Content-Type: '.$mime);
            }
            echo $response_data;
            exit;
        }

        ///=====================================================================
        /// return json body
        ///=====================================================================
        return $response_data;
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
     * set failure
     */
    public static function failure() { self::$success = false; }

    /**
     * @return bool
     */
    public static function isSuccess() { return self::$success; }
}
