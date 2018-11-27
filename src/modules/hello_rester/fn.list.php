<?php if(!defined("__RESTER__")) exit;

$result = array();
foreach (sql('list',cfg('rows')) as $row)
{
    $result[] = array(
        $arg[0].' Col0 : '.$row[0].'',
        $arg[0].' Col1 : '.$row[1].'',
        $arg[0].' Col2 : '.$row[2].''
    );
}

return $result;

