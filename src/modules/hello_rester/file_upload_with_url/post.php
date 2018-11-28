<?php if(!defined('__RESTER__')) exit;

rester::msg('File upload from external url example.');

$file = new file();
$data = $file->upload_from_url(rester::param('url'));
$data['file_fkey'] = 1;
$data['file_owner'] = 2;
return rester::sql('file','insert',$data);

