<?php

global $current_rester;

try
{
    include_once('./rester/common.php');
    $rester = new rester(cfg::module(), cfg::proc(), cfg::method(), cfg::request_body());
    $rester->set_public_access();
    $current_rester = $rester;
    rester_response::body($rester->run());
}
catch (Exception $e)
{
    rester_response::error($e->getMessage());
    rester_response::error_trace(explode("\n",$e->getTraceAsString()));
}

rester_response::run();
