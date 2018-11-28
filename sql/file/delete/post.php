<?php if(!defined('__RESTER__')) exit;

$key = rester::param('no');

if($pdo = db::get())
{
    $query = " SELECT * FROM `example_file` WHERE file_no={$key} LIMIT 1 ";

    $list = [];
    foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
    {
        $list[] = $row;
    }
    return $list;
}
return false;


