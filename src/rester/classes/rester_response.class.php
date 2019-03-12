<?php

/**
 * Class rester_response
 */
class rester_response
{
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = [];
    protected static $warning = [];
    protected static $error = [];
    protected static $error_trace = [];
    protected static $data = [];

    public static function run()
    {
        http_response_code(self::$response_code);
        header("Content-type: application/json; charset=UTF-8");

        $body = [];
        $body['success'] = self::$success;
        $body['msg'] = self::$msg;
        $body['data'] = self::$data;

        if(cfg::debug_mode())
        {
            $body['warning'] = self::$warning;
            $body['error'] = self::$error;
            $body['error_trace'] = self::$error_trace;
        }

        echo json_encode($body);
    }

    /**
     * reset data
     */
    public static function reset()
    {
        self::$success = true;
        self::$msg = [];
        self::$warning = [];
        self::$error = [];
        self::$data = [];
    }

    /**
     * @param array$data
     */
    public static function body($data)
    {
        self::$data = $data;
    }

    /**
     * Add message
     *
     * @param string $msg
     */
    public static function msg($msg) { self::$msg[] = $msg; }

    /**
     * Add warning message
     *
     * @param string $msg
     */
    public static function warning($msg) { self::$warning[] = $msg; }

    /**
     * Add error
     *
     * @param string $msg
     */
    public static function error($msg) { self::$error[] = $msg; self::failure(); }

    /**
     * Set error trace
     * @param array $data
     */
    public static function error_trace($data) { self::$error_trace = $data; self::failure(); }

    /**
     * set failure
     */
    public static function failure() { self::$success = false; }
}
