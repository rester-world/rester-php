<?php if(!defined('__RESTER__')) exit;

if($file_no = rester::param('no'))
{
    $width = rester::param('w');
    $height = rester::param('h');

    if($res = rester::sql('file','fetch',['file_no'=>$file_no]))
    {
        rester::set_header($res['file_type']);
        $file = new file($res);
        return $file->thumb($width,$height);
    }
    else
    {
        rester::failure();
        rester::msg("No image (in database).");
    }
}
return false;
