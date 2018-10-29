<?php if(!defined('__RESTER__')) exit;

/**
 * @return string 클라이언트의 접속 아이피
 */
function GetRealIPAddr()
{
    //check ip from share internet
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
    /**
     * @return array request headers
     */
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
 * current_module/fn.***.php 파일을 호출
 *
 * @param string $name
 *
 * @return bool|mixed
 */
function fn($name)
{
    $result = false;
    if($path = rester::path_fn($name))
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $arg = array_slice(func_get_args(),1);
        $result = include $path;
    }
    return $result;
}

/**
 * module/fn.***.php 파일 호출
 *
 * @param string $module
 * @param string $name
 *
 * @return bool|mixed
 */
function fnEX($module, $name)
{
    $result = false;
    if($path = rester::path_fn($name,$module))
    {
        $old = rester::change_module($module);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $arg = array_slice(func_get_args(),2);
        $result = include $path;
        rester::change_module($old);
    }
    return $result;
}

/**
 * module/config.ini 설정을 반환함
 *
 * @param null|string $section
 * @param null|string $key
 *
 * @return array|bool|mixed
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

    $result = $cfg[$section][$key];
    if(!$result) $result = cfg::Get($section,$key);

    return $result;
}

/**
 * @param string      $module
 * @param null|string $section
 * @param null|string $key
 *
 * @return array|bool|mixed
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
 */
function sql($name)
{
    $sql_result = false;
    if($path = rester::path_sql($name))
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $arg = array_slice(func_get_args(),1);
        $sql_result = include $path;
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
 */
function sqlEX($module, $name)
{
    $sql_result = false;
    if($path = rester::path_sql($name,$module))
    {
        $old = rester::change_module($module);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $arg = array_slice(func_get_args(),2);
        $sql_result = include $path;
        rester::change_module($old);
    }
    return $sql_result;
}

/**
 * @param null|string  $data
 * @param null|integer $timeout
 *
 * @return bool|null|string
 */
function cache($data=null,$timeout=30)
{
    $module = cfg::Get('module');
    $proc = cfg::Get('proc');
    return cacheEX($module,$proc,$data,$timeout);
}

/**
 * @param string $key
 * @param null|string $data
 * @param int  $timeout
 *
 * @return bool|null|string
 */
function cacheKey($key, $data=null, $timeout=60)
{
    $module = cfg::Get('module');
    $proc = cfg::Get('proc');
    return cacheEX($module,$proc,$data,$timeout,$key);
}

/**
 * @param string      $image_key
 * @param null|string $url
 * @param int         $timeout
 *
 * @return array
 */
function cacheImage($image_key, $url=null, $timeout=3600)
{
    $data = file_get_contents($url);
    $mime_type = mime_content_type($url);
    return array(
        'mime-type'=> cacheKey($image_key.'_mime',$mime_type,$timeout),
        'data'=> cacheKey($image_key,$data,$timeout)
    );
}

/**
 * @param string      $file_key
 * @param null|string $url
 * @param int         $timeout
 *
 * @return array
 */
function cacheFile($file_key, $url=null, $timeout=3600)
{
    $data = file_get_contents($url);
    $mime_type = mime_content_type($url);
    return array(
        'mime-type'=> cacheKey($file_key.'_mime',$mime_type,$timeout),
        'data'=> cacheKey($file_key,$data,$timeout)
    );
}


/**
 * @param string       $module
 * @param string       $proc
 * @param null|string  $data
 * @param null|integer $timeout
 *
 * @param string         $additional_key
 *
 * @return bool|null|string
 */
function cacheEX($module, $proc, $data=null, $timeout=30, $additional_key=null)
{
    $method = cfg::Get('method');
    $key = $module.'_'.$proc.'_'.$method;
    if($additional_key!==null) $key.= '_'.$additional_key;

    $redis_cfg = cfg::Get('cache');
    $redis = new Redis();
    $redis->connect($redis_cfg['host'], $redis_cfg['port']);
    if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

    if(!$data)
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
 * @param $url string
 * @param $saveto string
 */
function grab_image($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}

