<?php if(!defined('__RESTER__')) exit;

rester::msg("File upload example.");

$file = new file();

$list = array();
foreach ($file->upload('fname') as $row)
{
    $row['file_fkey'] = 1;
    $row['file_owner'] = 2;
    $list[] = rester::sql('file','insert',$row);
}

return $list;
