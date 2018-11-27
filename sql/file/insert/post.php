<?php if(!defined('__RESTER__')) exit;

$clean = [
    rester::param('fkey'),
    rester::param('owner'),
    rester::param('module'),
    rester::param('filename'),
    rester::param('file_local_name'),
    rester::param('file_size'),
    rester::param('file_type'),
    rester::param('file_desc'),
];

if($pdo = db::get())
{
    $query = "
        INSERT INTO `example_file`
        (`file_fkey`, `file_owner`, `file_module`, `file_name`, `file_local_name`, `file_size`, `file_type`, `file_desc`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $pdo->prepare($query)->execute([rand(0,255),rand(0,255)]);

    return [
        'inserted_id' => $pdo->lastInsertId()
    ];
}
else
{
    return false;
}

