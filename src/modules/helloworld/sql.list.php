<?php if(!defined("__RESTER__")) exit;

$sql_result = false;

// execute sql
// $sql_statement = " SELECT * FROM `table_name` WHERE 1 LIMIT 10 ";

for ($i=0; $i<$arg[0]; $i++)
{
    $sql_result[] = array('column1','column2','column3');
}


return $sql_result;
