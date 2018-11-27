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
