<?php if(!defined('__RESTER__')) exit;

$clean = [
    'file_fkey'=> rester::param('file_fkey'),
    'file_owner'=> rester::param('file_owner'),
    'file_module'=> rester::param('file_module'),
    'file_name'=> rester::param('file_name'),
    'file_local_name'=> rester::param('file_local_name'),
    'file_size'=> rester::param('file_size'),
    'file_type'=> rester::param('file_type'),
    'file_desc'=> rester::param('file_desc'),
];

if($pdo = db::get())
{
    $query = "
        INSERT INTO `example_file`
        (`file_fkey`, `file_owner`, `file_module`, `file_name`, `file_local_name`, `file_size`, `file_type`, `file_desc`)
        VALUES (:file_fkey, :file_owner, :file_module, :file_name, :file_local_name, :file_size, :file_type, :file_desc)
    ";
    $query = $pdo->prepare($query);
    $query->bindParam(':file_fkey', $clean['file_fkey']);
    $query->bindParam(':file_owner', $clean['file_owner']);
    $query->bindParam(':file_module', $clean['file_module']);
    $query->bindParam(':file_name', $clean['file_name']);
    $query->bindParam(':file_local_name', $clean['file_local_name']);
    $query->bindParam(':file_size', $clean['file_size']);
    $query->bindParam(':file_type', $clean['file_type']);
    $query->bindParam(':file_desc', $clean['file_desc']);
    if($query->execute())
    {
        $clean['file_no'] = $pdo->lastInsertId();
    }
    else
    {
        $clean = false;
    }
}
return $clean;

