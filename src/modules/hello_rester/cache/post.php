<?php if(!defined('__RESTER__')) exit;

rester_response::msg("Hello RESTer-SQL world!");
return [
    'Cache example (5 second)',
    'Current Datetime'.date("Y-m-d H:i:s")
];

