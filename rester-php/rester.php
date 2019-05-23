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
