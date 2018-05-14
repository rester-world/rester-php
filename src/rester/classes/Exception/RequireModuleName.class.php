<?php
namespace Rester\Exception;
/**
 * Class Schema
 *
 * 파일관련 예외처리 클래스
 */
class RequireModuleName extends ExceptionBase
{
    public function __construct()
    {
        $msg = "모듈명이 필요합니다.";

        // 모든 값이 할당되도록 합니다
        parent::__construct($msg);
    }
}

