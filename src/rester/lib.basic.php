<?php if(!defined('__RESTER__')) exit;

/**
 * @return string 클라이언트의 접속 아이피
 */
function GetRealIPAddr()
{
    //check ip from share internet
    $ip = '0.0.0.0';
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    //to check ip is pass from proxy
    else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * 모든 요청 헤더를 가져옴
 */
if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * @param string $name
 *
 * @return bool|mixed
 * @throws \Rester\Exception\ExceptionBase
 */
function fn($name)
{
    $result = false;
    if($path = rester::path_fn($name))
    {
        $arg = array_slice(func_get_args(),1);
        include $path;
    }
    return $result;
}

/**
 * @param string $module
 * @param string $name
 *
 * @return bool|mixed
 * @throws \Rester\Exception\ExceptionBase
 */
function fnEX($module, $name)
{
    $result = false;
    if($path = rester::path_fn($name,$module))
    {
        $arg = array_slice(func_get_args(),2);
        include $path;
    }
    return $result;
}

/**
 * @param null|string $section
 * @param null|string $key
 *
 * @return array|bool|mixed
 * @throws \Rester\Exception\ExceptionBase
 */
function cfg($section=null,$key=null)
{
    $cfg = array();
    if($path = rester::path_cfg())
    {
        $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
    }
    if($section===null) return $cfg;
    if($key===null) return $cfg[$section];
    return $cfg[$section][$key];
}

/**
 * @param string      $module
 * @param null|string $section
 * @param null|string $key
 *
 * @return array|bool|mixed
 * @throws \Rester\Exception\ExceptionBase
 */
function cfgEX($module, $section=null, $key=null)
{
    $cfg = array();
    if($path = rester::path_cfg($module))
    {
        $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
    }
    if($section===null) return $cfg;
    if($key===null) return $cfg[$section];
    return $cfg[$section][$key];
}

/**
 * Call sql file module/sql.{name}.php
 *
 * @param string $name
 *
 * @return bool|mixed
 * @throws \Rester\Exception\ExceptionBase
 */
function sql($name)
{
    $sql_result = false;
    if($path = rester::path_sql($name))
    {
        $arg = array_slice(func_get_args(),1);
        include $path;
    }
    return $sql_result;
}

/**
 * Call sql file modlue/sql.{name}.php
 *
 * @param string $module
 * @param string $name
 *
 * @return bool|mixed
 * @throws \Rester\Exception\ExceptionBase
 */
function sqlEX($module, $name)
{
    $sql_result = false;
    if($path = rester::path_sql($name,$module))
    {
        $arg = array_slice(func_get_args(),2);
        include $path;
    }
    return $sql_result;
}

/**
 * @param null|string  $data
 * @param null|integer $timeout
 *
 * @return bool|null|string
 * @throws \Rester\Exception\ExceptionBase
 */
function cache($data=null,$timeout=30)
{
    $module = cfg::Get('module');
    $proc = cfg::Get('proc');

    if($data===null)
    {
        $data = cacheEX($module, $proc);
    }
    else
    {
        cacheEX($module,$proc,$data,$timeout);
    }

    return $data;
}

/**
 * @param string       $module
 * @param string       $proc
 * @param null|string  $data
 * @param null|integer $timeout
 *
 * @return bool|null|string
 * @throws \Rester\Exception\ExceptionBase
 */
function cacheEX($module, $proc, $data=null, $timeout=30)
{
    $method = cfg::Get('method');
    $key = $module.'_'.$proc.'_'.$method;

    $redis_cfg = cfg::Get('cache');
    $redis = new Redis();
    $redis->connect($redis_cfg['host'], $redis_cfg['port']);
    if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

    if($data===null)
    {
        $data = $redis->get($key);
    }
    else
    {
        $redis->set($key,$data,$timeout);
    }
    $redis->close();
    return $data;
}

/**
 * @param array $arr
 *
 * @return bool
 */
function is_assoc($arr)
{
    $keys = array_keys($arr);
    return array_keys($keys) !== $keys;
}

/**
 * @param $key
 * @param $function
 *
 * @throws \Rester\Exception\ExceptionBase
 */
function verify_header($key, $function)
{
    if(!is_callable($function)) throw new \Rester\Exception\ExceptionBase("2번째 파라미터는 호출 가능한 함수여야 합니다.");

    $data = $function(cfg::Get('request-headers',$key));
    if($data) rester::set_request_header($key, $data);
}

/**
 * @param $key
 * @param $function
 *
 * @throws \Rester\Exception\ExceptionBase
 */
function verify_param($key, $function)
{
    if(!is_callable($function)) throw new \Rester\Exception\ExceptionBase("2번째 파라미터는 호출 가능한 함수여야 합니다.");

    $data = $function(cfg::Get('request-body',$key));
    if($data) rester::set_request_param($key, $data);
}

