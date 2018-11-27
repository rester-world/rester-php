<?php if(!defined('__RESTER__')) exit;

rester::msg("Hello RESTer-SQL world!");

$session_id = session::id();
return array(
'title'=>'Token을 이용한 접근제어',
'session_id'=>$session_id,
);

