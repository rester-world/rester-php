<?php if(!defined('__RESTER__')) exit;

rester_response::msg("Hello RESTer-SQL world!");

$id = request_param('session_id');
$token = session::set_token($id);

return array(
    'session_id'=>$id,
    'token'=>$token
);
