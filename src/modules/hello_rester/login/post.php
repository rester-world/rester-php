<?php if(!defined('__RESTER__')) exit;

rester::msg("Hello RESTer-SQL world!");

$id = rester::param('session_id');
$token = session::set($id);

return array(
    'session_id'=>$id,
    'token'=>$token
);
