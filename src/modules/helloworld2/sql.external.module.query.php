<?php if(!defined("__RESTER__")) exit;

$sql_result = false;
/** @var integer $arg */
if($rows = $arg[0])
{
    try
    {
        $pdo = db::get();

        foreach ($pdo->select(" SELECT * FROM `example` WHERE 1 LIMIT {$arg[0]} ") as $row)
        {
            $sql_result[] = array($row['no'], $row['key'],$row['value']);
        }
    }
    catch (Exception $e)
    {
        $sql_result = false;
    }
}

return $sql_result;
