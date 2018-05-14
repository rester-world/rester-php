<?php
/**
 *	@class		verify
 *	@author	Kevin Park (kevinpark<>webace.co.kr)
 *	@author	주식회사 다이음.
 *	@version	1.0
 *	@brief		request 로 넘어오는 데이터를 검증
 *              header : 헤더정보
 *              param : form-data 또는 json body 형식의 데이터 검증
 *	@date	    2018.05.04 - 생성
 */
class verify
{
    /**
     * @param string $key
     * @param null $function
     */
    public static function param($key, $function=null)
    {
        if(is_callable($function))
        {
            $data = $function(cfg::Get('request-body',$key));
            if($data) rester::set_request_header($key, $data);
        }
        else
        {
            rester::error("익명함수 형식이 잘못되었습니다.");
        }
    }

    /**
     * @param string $key
     * @param null $function
     */
    public static function header($key, $function)
    {
        if(is_callable($function))
        {
            $data = $function(cfg::Get('request-headers',$key));
            if($data) rester::set_request_header($key, $data);
        }
        else
        {
            rester::error("익명함수 형식이 잘못되었습니다.");
        }
    }

}
