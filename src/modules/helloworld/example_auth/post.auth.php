<?php if(!defined("__RESTER__")) exit;

// 호출 순서
// 1. post.verify.ini __OK
// 2. post.auth.php __OK
// 3. post.php

$id = rester::param_header('x-auth-id');
$token = rester::param_header('x-auth-token');

$sql = '데이터베이스에 접속하여 id 와 token을 가지고 비교';

// 인증 실패
if(false)
{
    rester::error('사용자 인증에 실패 하였습니다.');
}


