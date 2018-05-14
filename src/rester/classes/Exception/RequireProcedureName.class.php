<?php
namespace Rester\Exception;
/**
 * Class Schema
 *
 * 파일관련 예외처리 클래스
 */
class RequireProcedureName extends ExceptionBase
{
    const ERR_MODULE_NAME = 0x00000001;

    private $msg = array(
        self::ERR_MODULE_NAME => '모듈명 정의 필요'
    );

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        // 처리할 코드

        // 모든 값이 할당되도록 합니다
        parent::__construct($message, $code, $previous);
    }
}

