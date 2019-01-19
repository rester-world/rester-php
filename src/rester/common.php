<?php
/**
 *  @file common.php
 *  @brief  가장 먼저 실행되는 파일이면서
 *          각종 초기화를 수행함
 */
define('__RESTER__', TRUE);

//-------------------------------------------------------------------------------
/// set php.ini
//-------------------------------------------------------------------------------
set_time_limit(0);
ini_set("session.use_trans_sid", 0); // PHPSESSID 를 자동으로 넘기지 않음
ini_set("url_rewriter.tags","");     // 링크에 PHPSESSID 가 따라다니는것을 무력화
ini_set("default_socket_timeout",500);

ini_set("memory_limit", "1000M");     // 메모리 용량 설정.
ini_set("post_max_size","1000M");
ini_set("upload_max_filesize","1000M");

//-------------------------------------------------------------------------------
/// Set the global variables [_POST / _GET / _COOKIE]
/// initial a post and a get variables.
/// if not support short global variables, will be available.
//-------------------------------------------------------------------------------
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

//-------------------------------------------------------------------------------
/// if get magic quotes gpc is on, set off
/// set magic_quotes_gpc off
//-------------------------------------------------------------------------------
if (get_magic_quotes_gpc())
{

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

//-------------------------------------------------------------------------------
/// add slashes
//-------------------------------------------------------------------------------
if(is_array($_POST)) array_walk_recursive($_POST, function(&$item){ $item = addslashes($item); });
if(is_array($_GET)) array_walk_recursive($_GET, function(&$item){ $item = addslashes($item); });
if(is_array($_COOKIE)) array_walk_recursive($_COOKIE, function(&$item){ $item = addslashes($item); });

//-------------------------------------------------------------------------------
/// Load basic classes
//-------------------------------------------------------------------------------
require_once dirname(__FILE__).'/classes/cfg.class.php';
require_once dirname(__FILE__).'/classes/db.class.php';
require_once dirname(__FILE__).'/classes/file.class.php';
require_once dirname(__FILE__).'/classes/schema.class.php';
require_once dirname(__FILE__).'/classes/session.class.php';
require_once dirname(__FILE__).'/classes/rester.class.php';


//-------------------------------------------------------------------------------
/// autoloader
/// namespace 는 폴더로 구분
/// classExt 폴더만 참조함
//-------------------------------------------------------------------------------
spl_autoload_register(function($class_name)
{
    $class_name = implode('/',array_filter(explode('\\',$class_name), function($item) { return ($item!='Rester');}));
    $classExt = dirname(__FILE__).'/classExt/'.$class_name.'.class.php';
    if(is_file($classExt)) include_once $classExt;
    else {
        echo "No search class file : ".$class_name;
        exit;
    }
});

//-------------------------------------------------------------------------------
/// Include lib files
/// 01. Default library include
/// 02. Library folder include
//-------------------------------------------------------------------------------
include_once(dirname(__FILE__) . '/common.lib.php');

foreach (glob(dirname(__FILE__) . '/lib/lib.*.php') as $filename)
{
    include_once $filename;
}
