<?php
define('__RESTER__', TRUE);

global /** @var rester $current_rester */
$current_rester;

//-------------------------------------------------------------------------------
/// include classes
//-------------------------------------------------------------------------------
require_once dirname(__FILE__) . '/../rester-core/common.php';
require_once dirname(__FILE__) . '/resterPHP.class.php';

//-------------------------------------------------------------------------------
/// Include lib files
//-------------------------------------------------------------------------------
foreach (glob(dirname(__FILE__) . '/../exten_lib/lib.*.php') as $filename)
{
    include_once $filename;
}

/**
 * @param string $module
 * @param string $proc
 * @param string $method
 * @param array  $query
 *
 * @return mixed
 */
function request_module($module, $proc, $method, $query=[])
{
    global $current_rester;
    $old_rester = $current_rester;
    $res = false;

    try
    {
        if($token = request_param('token')) $query['token'] = $token;
        if($secret = request_param('secret')) $query['secret'] = $secret;

        $current_rester = new resterPHP($module, $proc, $method, $query);
        $res = $current_rester->run($old_rester);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
    }

    $current_rester = $old_rester;
    return $res;
}

/**
 * @param string $proc
 * @param string $method
 * @param array  $query
 *
 * @return mixed
 */
function request_procedure($proc, $method, $query=[])
{
    global $current_rester;
    $old_rester = $current_rester;
    $res = false;

    try
    {
        $current_rester = new resterPHP($current_rester->module(), $proc, $method, $query);
        $res = $current_rester->run($old_rester);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
    }

    $current_rester = $old_rester;
    return $res;
}

try
{
    // config init
    cfg::init();

    // 오류출력설정
    if (cfg::debug_mode())
        error_reporting(E_ALL ^ (E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED));
    else
        error_reporting(0);

    // timezone 설정
    date_default_timezone_set(cfg::timezone());

    $rester = new resterPHP(cfg::module(), cfg::proc(), cfg::method(), cfg::request_body());
    $rester->set_public_access();
    $current_rester = $rester;
    rester_response::body($rester->run());
}
catch (Exception $e)
{
    rester_response::failed(sprintf("%02s",$e->getCode()),$e->getMessage());
    rester_response::error_trace(explode("\n",$e->getTraceAsString()));
}
rester_response::run();
