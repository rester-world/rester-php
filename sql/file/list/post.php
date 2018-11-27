<?php if(!defined('__RESTER__')) exit;

$fkey = rester::param('file_fkey');
$owner = rester::param('file_owner');

if($pdo = db::get())
{
    $query = " SELECT * FROM `example_file` WHERE 1 ";
    if($owner) $query.=" AND file_owner={$owner} ";
    if($fkey) $query.=" AND file_fkey={$fkey} ";

    $list = [];
    foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
    {
        $list[] = $row;
    }
    return $list;
}
return false;


