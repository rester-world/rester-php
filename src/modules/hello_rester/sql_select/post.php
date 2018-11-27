<?php if(!defined('__RESTER__')) exit;

rester::msg("Hello RESTer-SQL world!");

return  rester::sql('example', 'select', array());
