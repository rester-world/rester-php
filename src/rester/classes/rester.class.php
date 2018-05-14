<?php
/**
 *  @class    rester
 *  @author   박경종(kevinpark@webace.co.kr)
 *  @brief    모듈
 *  @date     2018.05.02 - 생성
 *  
 *  @update   2018.05.02 -
 */
class rester
{
    const path_module = 'modules';
    const file_verify = 'verify.ini';
    const file_verify_func = 'verify.php';
    const file_config = 'config.ini';
    const file_auth = 'auth.php';

    protected static $request_headers = array();
    protected static $request_param = array();

    protected static $response_headers = array();
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $err = false;
    protected static $err_msg = array();

    /**
     * Add error message & set failure
     * @param string $msg error message
     */
    public static function error($msg)
    {
        self::$err = true;
        self::$err_msg[] = $msg;
    }

    /**
     * execute header();
     */
    protected static function run_headers()
    {
        foreach (self::$response_headers as $k=>$v)
        {
            header($k.': '.$v);
        }
    }

    /**
     * run rester
     *
     * 1. auth check
     * 2. verify parameter
     * 3. check error
     * 4. include procedure
     * 5. echo response code
     * 6. echo header
     * 7. echo body(json)
     * @throws Exception
     */
    public static function run()
    {
        // check request parameter
        if($path_verify = self::path_verify())
        {
            $schema = new schema($path_verify);
            if($data = $schema->validate(cfg::Get('request-body')))
                foreach($data as $k => $v) rester::set_request_param($k, $v);

            if($data = $schema->validate(cfg::Get('request-headers')))
                foreach($data as $k => $v) rester::set_request_header($k, $v);
        }

        // check request param with func
        if($path_verify_func = self::path_verify_func())
        {
            include $path_verify_func;
        }


        // check request auth
        if($path_auth = self::path_auth())
        {
            include $path_auth;
        }

        // 오류사항이 있을 때
        if(self::$err)
        {
            $body = json_encode(array(
                'success' => false,
                'msg' => self::$err_msg
            ));
        }
        else
        {
            // 해당 프로시저 파일검사
            if(false === ($path_proc = self::path_proc()))
            {
                self::$response_code = 404;
                self::error("해당 파일을 찾을 수 없습니다.");
            }

            ob_start();
            include $path_proc;
            $body = ob_get_contents();
            ob_end_clean();
        }

        http_response_code(self::$response_code);
        self::run_headers();
        echo $body;
    }

    /**
     * Path to module
     * @return string
     */
    protected static function path_module()
    {
        return dirname(__FILE__).'/../../'.self::path_module;
    }

    /**
     * Path to procedure file
     * @param null $module_name 모듈 이름
     * @param null $proc_name   프로시저 이름
     * @return bool|string      실패 | 경로
     */
    protected static function path_proc($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');
        if(null === $proc_name) $proc_name = cfg::Get('proc');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            strtolower(cfg::Get('method')).'.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to fn file
     *
     * @param string $name
     * @param string $module_name
     * @return bool|string
     */
    public static function path_fn($name, $module_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            'fn.'.$name.'.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to sql file
     *
     * @param string $name
     * @param string $module_name
     * @return bool|string
     */
    public static function path_sql($name, $module_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            'sql.'.$name.'.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to config file
     * @param null $module_name 모듈명
     * @return bool|string 실패 | 경로
     */
    public static function path_cfg($module_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            self::file_config
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     * @param null $module_name
     * @param null $proc_name
     * @return bool|string
     */
    protected static function path_verify($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');
        if(null === $proc_name) $proc_name = cfg::Get('proc');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            strtolower(cfg::Get('method')).'.'.self::file_verify
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     * @param null $module_name
     * @param null $proc_name
     * @return bool|string
     */
    protected static function path_verify_func($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');
        if(null === $proc_name) $proc_name = cfg::Get('proc');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            strtolower(cfg::Get('method')).'.'.self::file_verify_func
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to auth file
     * @param null $module_name
     * @param null $proc_name
     * @return bool|string
     */
    protected static function path_auth($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = cfg::Get('module');
        if(null === $proc_name) $proc_name = cfg::Get('proc');

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            strtolower(cfg::Get('method')).'.'.self::file_auth
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * 요청헤더 설정
     * @param $key
     * @param $value
     */
    public static function set_request_header($key, $value)
    {
        if($key && $value) self::$request_headers[$key] = $value;
    }


    /**
     * 요청바디 설정
     * @param $key
     * @param $value
     */
    public static function set_request_param($key, $value)
    {
        if($key && $value) self::$request_param[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function param_header($key)
    {
        if(isset(self::$request_headers[$key])) return self::$request_headers[$key];
        return false;
    }

    /**
     * 요청값 반환
     * @param $key
     * @return bool|mixed
     */
    public static function param($key)
    {
        if(isset(self::$request_param[$key])) return self::$request_param[$key];
        return false;
    }

    /**
     * @param $key
     * @param $value
     */
    public static function set_response_header($key, $value)
    {
        self::$response_headers[$key] = $value;
    }

    /**
     * @param integer $code
     */
    public static function set_response_code($code)
    {
        self::$response_code = $code;
    }
}
