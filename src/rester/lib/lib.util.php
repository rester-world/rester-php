<?php if(!defined('__RESTER__')) exit;

/**
 * 토큰생성
 *
 * @param int $len token length
 * @return string token
 */
function gen_token($len=20)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.';
    $token = '';
    for ($i = 0; $i < $len; $i++) {
        $token = $characters[rand(0, strlen($characters))];
    }
    return $token;
}
