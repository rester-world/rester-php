<?php
namespace Rester\Exception;
/**
 * Class ExceptionBase
 *
 * @package Rester\Exception
 */
class ExceptionBase extends \Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    // 객체의 사용자 문자열 표현
    public function __toString()
    {
        return "{$this->message}";
    }
}

