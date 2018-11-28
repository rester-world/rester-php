<?php if(!defined('__RESTER__')) exit;

rester::msg("File list example.");

return [
    'all'=>rester::sql('file','list', array()),
    'owner'=>rester::sql('file','list', array('file_owner'=>2)),
    'fkey'=>rester::sql('file','list', array('file_fkey'=>1)),
];
