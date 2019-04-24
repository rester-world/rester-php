<?php

/**
 * Class rester_response
 */
class rester_response
{
    /**
     * @var int 응답코드
     */
    protected static $response_code = 200;

    const code_success          = '00';
    const code_require_login    = '01';
    const code_system_error     = '02';
    const code_parameter        = '11';
    const code_config           = '12';
    const code_request_method   = '13';
    const code_etc              = '99';

    /**
     * @var array 응답코드 목록
     */
    protected static $res_code_list = [
        self::code_success          =>'성공',
        self::code_require_login    =>'로그인 필요',
        self::code_parameter        =>'호출인자 오류',
        self::code_config           =>'환경설정 오류',
        self::code_request_method   =>'호출 메서드 오류',
        self::code_etc              =>'Etc.'
    ];

    protected static $success = true;
    protected static $res_code = '00';
    protected static $session = false;
    protected static $msg = false;
    protected static $warning = false;
    protected static $error = false;
    protected static $error_trace = false;
    protected static $data = false;

    /**
     * 로그인 실패
     *
     * @param string $msg
     */
    public static function failed_login($msg='') { self::failed(self::code_require_login, $msg); }

    /**
     * @param string $msg
     */
    public static function failed_param($msg='') { self::failed(self::code_parameter, $msg); }

    /**
     * @param string $msg
     */
    public static function failed_custom($msg) { self::failed(self::code_etc, $msg); }

    /**
     * @param string $code
     * @param string $msg
     */
    public static function failed($code, $msg='')
    {
        self::$res_code = $code;
        self::error(self::$res_code_list[$code]);
        if($msg) self::error($msg);
    }

    /**
     * render result
     */
    public static function run()
    {
        http_response_code(self::$response_code);
        header("Content-type: application/json; charset=UTF-8");

        $body = [];
        $body['success'] = self::$success;
        $body['retCode'] = self::$res_code;
        if(self::$session) $body['session'] = self::$session;
        if(self::$msg) $body['msg'] = self::$msg;
        if(self::$data) $body['data'] = self::$data;

        if(cfg::debug_mode())
        {
            if(self::$warning) $body['warning'] = self::$warning;
            if(self::$error) $body['error'] = self::$error;
            if(self::$error_trace) $body['errorTrace'] = self::$error_trace;
        }

        echo json_encode($body);
    }

    /**
     * reset data
     */
    public static function reset()
    {
        self::$success = true;
        self::$res_code = '00';
        self::$session = false;
        self::$msg = false;
        self::$warning = false;
        self::$error = false;
        self::$error_trace = false;
        self::$data = false;
    }

    /**
     * @param array $data
     */
    public static function body($data) { self::$data = $data; }

    /**
     * @param array $data
     */
    public static function session($data) { self::$session = $data; }

    /**
     * Add message
     *
     * @param string $msg
     */
    public static function msg($msg) { if(!self::$msg) self::$msg=[]; self::$msg[] = $msg; }

    /**
     * Add warning message
     *
     * @param string $msg
     */
    public static function warning($msg) { if(!self::$warning) self::$warning=[]; self::$warning[] = $msg; }

    /**
     * Add error
     *
     * @param string $msg
     */
    public static function error($msg) { if(!self::$error) self::$error=[]; self::$error[] = $msg; self::failure(); }

    /**
     * Set error trace
     * @param array $data
     */
    public static function error_trace($data) { if(!self::$error_trace) self::$error_trace=[]; self::$error_trace = $data; self::failure(); }

    /**
     * set failure
     */
    public static function failure() { self::$success = false; }
}
