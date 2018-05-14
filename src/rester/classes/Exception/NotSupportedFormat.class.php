<?php
namespace Rester\Exception;
/**
 * Class NotSupportedFormat
 *
 * @package Rester\Exception
 */
class NotSupportedFormat extends ExceptionBase
{
    const ERR_FORMAT        = 0x00000001;
    const ERR_FILE_TYPE     = 0x00000002;
    const ERR_CONTENT       = 0x00000004;
    const ERR_FUNCTION_TYPE = 0x00000008;
    const ERR_PARAM         = 0x00000010;
    const ERR_NO_FIELD      = 0x00000020;
    const ERR_FILTER_TYPE   = 0x00000040;
    const ERR_VALIDATE_DATA = 0x00000080;

    private $msg = array(
        self::ERR_FORMAT => '지원되지 않는 스키마 형식(지원형식 : json string | json file | array |.ini file path)',
        self::ERR_FILE_TYPE=> '지원되지 않는 파일 형식(json,ini 파일지원)',
        self::ERR_CONTENT=> '지원되지 않는 스키마 포멧',
        self::ERR_FUNCTION_TYPE => '익명함수 오류',
        self::ERR_PARAM => '파라미터 오류',
        self::ERR_NO_FIELD => '스키마에 없는 필드',
    );

    public function __construct($message, $code = 0)
    {
        $msg = "";
        parent::__construct($message, $code);
    }
}

