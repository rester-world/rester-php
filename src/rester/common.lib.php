<?php if(!defined('__RESTER__')) exit;

/**
 * fn.***.php 파일을 호출
 *
 * @param string $name
 * @param null|string $module
 *
 * @return bool|mixed
 * @throws Exception
 */
function fn($name, $module=null)
{
    $result = false;
    $old = null;
    if($module) $old = cfg::change_module($module);
    if($path = rester::path_fn($name))
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $arg = array_slice(func_get_args(),1);
        $result = include $path;
    }
    if($old) cfg::change_module($old);
    return $result;
}

/**
 * @param array $arr
 *
 * @return bool
 */
function is_assoc($arr)
{
    $keys = array_keys($arr);
    return array_keys($keys) !== $keys;
}

/**
 * @param $url string
 * @param $saveto string
 */
function grab_image($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}

