<?php if(!defined('__RESTER__')) exit;

$rows = rester::cfg('rows');

if($pdo = db::get())
{
    $query = " SELECT * FROM `example` LIMIT {$rows} ";
    $list = [];
    foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
    {
        $list[] = $row;
    }
    return $list;
}
return false;


