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
    const file_auth = 'auth.php';
    const file_verify_func = 'verify.php';
    const file_verify = 'verify.ini';
    const file_config = 'config.ini';
    const file_schema = 'table.ini';

    protected static $request_headers = array();
    protected static $request_param = array();

    protected static $response_headers = array();
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $err = false;
    protected static $err_msg = array();

    protected static $current_module;

    /**
     * @param $module string
     *
     * @return string
     */
    public static function change_module($module)
    {
        $old_module = self::$current_module;
        self::$current_module = $module;
        return $old_module;
    }

    /**
     * Add error message & set failure
     *
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
            header(trim($k).': '.$v);
        }
    }

    /**
     * run rester
     *
     * 1. verify parameter
     * 3. check error
     * 4. include procedure
     * 5. echo response code
     * 6. echo header
     * 7. echo body(json)
     * @throws Exception
     */
    public static function run()
    {
        self::$current_module = cfg::Get('module');

        // check request parameter
        if($path_verify = self::path_verify())
        {
            $schema = new \Rester\Data\Schema($path_verify);

            // check header
            try
            {
                if($data = $schema->validate(cfg::Get('request-headers')))
                    foreach($data as $k => $v)
                        rester::set_request_header($k, $v);
            }
            catch (Exception $e)
            {
                self::error('request-headers : '.$e->__toString());
            }

            // check body | query string
            try
            {
                if($data = $schema->validate(cfg::Get('request-body')))
                    foreach($data as $k => $v)
                        rester::set_request_param($k, $v);
            }
            catch (Exception $e)
            {
                self::error('request-body | query: '.$e->__toString());
            }

        }

        // check request param with func
        if($path_verify_func = self::path_verify_func())
        {
            include $path_verify_func;
        }

        // 검증파일이 있으면 필수입력 검사
        if($path_verify = self::path_verify())
        {
            $schema = new \Rester\Data\Schema($path_verify);
            try
            {
                $schema->check_require(self::all_params());
            }
            catch (Exception $e)
            {
                self::error($e->__toString());
            }
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

        // 응답 결과 코드 설정
        http_response_code(self::$response_code);
        // 응답헤더 출력
        self::run_headers();
        // 저장된 $body 출력
        echo $body;
    }

    /**
     * Path to module
     *
     * @return string
     */
    protected static function path_module()
    {
        return dirname(__FILE__).'/../../'.self::path_module;
    }

    /**
     * Path to procedure file
     *
     * @param null|string $module_name 모듈 이름
     * @param null|string $proc_name   프로시저 이름
     * @return bool|string      실패 | 경로
     */
    protected static function path_proc($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;
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
     * @param string     $name
     * @param null|string $module_name
     *
     * @return bool|string
     */
    public static function path_fn($name, $module_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;

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
     * @param      $name
     * @param null $module_name
     *
     * @return bool|string
     */
    public static function path_sql($name, $module_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;

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
     *
     * @param null|string $module_name
     *
     * @return bool|string
     */
    public static function path_cfg($module_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            self::file_config
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to schema file
     *
     * @param null|string $name
     *
     * @return bool|string
     */
    public static function path_schema($name=null)
    {
        $schema = self::file_schema;
        if(!($name===null))
        {
            $_schema = explode('.',$schema);
            $schema = implode('.',array(
                $_schema[0],$name,$_schema[1]
            ));
        }

        $path = implode('/',array(
            self::path_module(),
            self::$current_module,
            $schema
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @param null $module_name
     * @param null $proc_name
     *
     * @return bool|string
     */
    protected static function path_verify($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;
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
     *
     * @param null $module_name
     * @param null $proc_name
     *
     * @return bool|string
     */
    protected static function path_verify_func($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;
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
     *
     * @param null|string $module_name
     * @param null|string $proc_name
     *
     * @return bool|string
     */
    protected static function path_auth($module_name = null, $proc_name = null)
    {
        if(null === $module_name) $module_name = self::$current_module;
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
     *
     * @param string $key
     * @param string $value
     */
    public static function set_request_header($key, $value)
    {
        if($key && ($value || $value===0)) self::$request_headers[$key] = $value;
    }


    /**
     * 요청바디 설정
     *
     * @param string $key
     * @param string $value
     */
    public static function set_request_param($key, $value)
    {
        if($key && ($value || $value===0)) self::$request_param[$key] = $value;
    }

    /**
     * @param null|string $key
     * @return mixed
     */
    public static function param_header($key=null)
    {
        if(isset(self::$request_headers[$key])) return self::$request_headers[$key];
        if($key == null) return self::$request_headers;
        return false;
    }

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
     * @return array
     */
    public static function all_params()
    {
        return array_merge(self::$request_param,self::$request_headers);
    }

    /**
     * @param string $key
     * @param string $value
     */
    public static function set_response_header($key, $value)
    {
        self::$response_headers[trim($key)] = $value;
    }

    /**
     * @param integer $code
     */
    public static function set_response_code($code)
    {
        self::$response_code = $code;
    }
}
