<?php
/**
 *  @file common.php
 *  @brief  가장 먼저 실행되는 파일이면서
 *          각종 초기화를 수행함
 *
 * 0. Global Value 초기화
 * 1. DB 연결 초기화
 * 2. restapi header 설정
 * 3. error 출력 초기화
 * 4. php.ini 설정 초기화
 * 5. session 초기화
 * 6.
 *  
 */
define('__RESTER__', TRUE);

// namespace 설정이 적용된 autoloader
spl_autoload_register(function($class_name)
{
    $class_name = implode('/',array_filter(explode('\\',$class_name), function($item) { return ($item!='Rester');}));
    include_once(dirname(__FILE__).'/classes/'.$class_name.'.class.php');
});

// 01. Default library include
include_once(dirname(__FILE__) . '/lib.basic.php');

// 02. Library folder include
foreach (glob(dirname(__FILE__) . '/lib/lib.*.php') as $filename)
{
    include_once $filename;
}

// 오류출력설정
if(cfg::Get('default', 'debug_mode')) error_reporting(E_ALL ^ (E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED));
else error_reporting(0);

// timezone 설정
date_default_timezone_set(cfg::Get('default','timezone'));

// set php.ini
set_time_limit(0);
ini_set("memory_limit", "10M");     // 메모리 용량 설정.
ini_set("session.use_trans_sid", 0); // PHPSESSID를 자동으로 넘기지 않음
ini_set("url_rewriter.tags","");     // 링크에 PHPSESSID가 따라다니는것을 무력화
ini_set( 'session.save_handler', 'files' );

if (isset($SESSION_CACHE_LIMITER)) @session_cache_limiter($SESSION_CACHE_LIMITER);
else @session_cache_limiter("no-cache, must-revalidate");

session_cache_expire();
session_set_cookie_params ( 0, "/", cfg::Get('default','session_domain'));


// session is 세팅
if($_GET['PHPSESSID']) session_id($_GET['PHPSESSID']);

// 세션시작
session_start();

// Set the global variables [_POST / _GET / _COOKIE]
// initial a post and a get variables.
// if not support short grobal variables, will be avariable.
if (isset($HTTP_POST_VARS) && !isset($_POST))
{
    $_POST   = &$HTTP_POST_VARS;
    $_GET    = &$HTTP_GET_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
    $_COOKIE = &$HTTP_COOKIE_VARS;
    $_ENV    = &$HTTP_ENV_VARS;
    $_FILES  = &$HTTP_POST_FILES;
    if (!isset($_SESSION))
        $_SESSION = &$HTTP_SESSION_VARS;
}

// force to set register globals off
// http://kldp.org/node/90787
if(ini_get('register_globals'))
{
    foreach($_GET as $key => $value) { unset($$key); }
    foreach($_POST as $key => $value) { unset($$key); }
    foreach($_COOKIE as $key => $value) { unset($$key); }
}

function stripslashes_deep($value)
{
    $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    return $value;
}

// if get magic quotes gpc is on, set off
// set magic_quotes_gpc off
if (get_magic_quotes_gpc())
{

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

// add slashes
foreach($_POST as $k => $v)
{
    if(is_array($v))
    {
        foreach($v as $kk => $vv)
        {
            $_POST[$k][$kk] = addslashes($vv);
        }
    }
    else
    {
        $_POST[$k] = addslashes($v);
    }
}

foreach($_GET as $k => $v)
{
    $_GET[$k] = addslashes($v);
}

foreach($_COOKIE as $k => $v)
{
    $_COOKIE[$k] = addslashes($v);
}

// Response header setting
foreach (cfg::Get('response_headers') as $key=>$value)
{
    rester::set_response_header($key,$value);
}
