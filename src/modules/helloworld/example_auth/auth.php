<?php if(!defined("__RESTER__")) exit;

// 프로시저 별 권한 검사

$id = rester::param_header('x-auth-id');
$token = rester::param_header('x-auth-token');

// 인증 실패
if(false)
{
    rester::error('사용자 인증에 실패 하였습니다.');
}


