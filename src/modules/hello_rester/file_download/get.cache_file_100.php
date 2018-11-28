<?php if(!defined('__RESTER__')) exit;

if($file_no = rester::param('no'))
{
    if($res = rester::sql('file','fetch',['file_no'=>$file_no]))
    {
        $file = new file($res);
        return $file->download();
    }
    else
    {
        rester::failure();
        rester::msg("No image (in database).");
    }
}
return false;

