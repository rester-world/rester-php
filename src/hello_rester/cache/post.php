<?php if(!defined('__RESTER__')) exit;

rester_response::msg("Cached!");
return [
    'Cache example (5 second)',
    'Current Datetime '.date("Y-m-d H:i:s")
];

