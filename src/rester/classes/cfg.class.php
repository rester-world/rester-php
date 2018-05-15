<?php
use \Rester\Exception\ExceptionBase;
/**
 *	@class		cfg
 *	@author	Kevin Park (kevinpark<>webace.co.kr)
 *	@author	주식회사 다이음.
 *	@version	1.0
 *	@brief		기본 설정 정보
 *	@date	    2018.04.25 - 생성
 */
class cfg
{
    const query_version = 'v';
    const query_module = 'm';
    const query_proc = 'proc';
    private static $name = 'rester.ini';  // 파일명
    private static $data;	// 설정정보

    const key_module = 'm';
    const key_function = 'fn';

    protected static $default = array(

        'response_headers'=>array(
            'Content-type'=>'application/json; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
            "Access-Control-Allow-Methods" => '*',
            "Access-Control-Allow-Headers" => '*'
        ),

        'response_body_skel' => array(
            'success'=>false,
            'msg'=>'',
            'data'=>array()
        ),

        'access_control'=>array(
            'allows_origin'=>'*',
            'allows_headers'=>'Content-Type',
            'allows_method'=>'GET,POST',
        ),

        'default'=>array(
            'session_domain'=>'.example.com',
            'debug_mode'=>true,
            'timezone'=>'Asia/Seoul'
        )

    );

    /**
     * 기본정보 초기화
     *
     * @throws ExceptionBase
     */
    private static function init()
    {
        // 환경설정 파일 로드
        $path = dirname(__FILE__).'/../../../cfg/'.self::$name;
        if(is_file($path)) $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        else throw new ExceptionBase("환경설정 파일이 없습니다.(rester.ini");

        // 기본값 설정
        foreach (self::$default as $k=>$v)
        {
            foreach ($v as $kk => $vv)
            {
                if (!isset($cfg[$k][$kk])) $cfg[$k][$kk] = $vv;
            }
        }

        if($cfg['access_control']['allows_origin']!='*') $cfg['access_control']['allows_origin'] = explode(',', $cfg['access_control']['allows_origin']);
        $cfg['access_control']['allows_headers'] = explode(',', $cfg['access_control']['allows_headers']);
        $cfg['access_control']['allows_method'] = explode(',', $cfg['access_control']['allows_method']);
        array_walk_recursive($cfg, function(&$v) { $v = trim($v); });


        // 버전명 검사
        if(preg_match('/^[0-9][0-9.]*$/i',$_GET[self::query_version],$matches))
        {
            $cfg['version'] = $matches[0];
        }
        else
        {
            if($_GET[self::query_version]=='')
            {
                throw new ExceptionBase("최상위 폴더로는 접근이 불가합니다.");
                //rester::set_response_code(400);
                //rester::error('최상위 폴더로는 접근이 불가합니다.');
            }
            else
            {
                throw new ExceptionBase("버전명이 잘못되었습니다.");
                //rester::set_response_code(400);
                //rester::error('버전명이 잘못되었습니다.');
            }
        }

        // 모듈명 검사
        if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[self::query_module]),$matches))
        {
            $cfg['module'] = $matches[0];
        }
        else
        {
            throw new ExceptionBase("모듈명이 잘못되었습니다.");
            //rester::set_response_code(400);
            //rester::error('모듈명이 잘못되었습니다.');
        }

        // 프로시저명 검사
        if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[self::query_proc]),$matches))
        {
            $cfg['proc'] = $matches[0];
        }
        else
        {
            throw new ExceptionBase("프로시저명이 잘못되었습니다.");
            //rester::set_response_code(400);
            //rester::error('프로시저명이 잘못되었습니다.');
        }

        // 허용 method 검사
        if(in_array($_SERVER['REQUEST_METHOD'],$cfg['access_control']['allows_method']))
        {
            $cfg['method'] = $_SERVER['REQUEST_METHOD'];
        }
        else
        {
            throw new ExceptionBase("METHOD 형식이 잘못되었습니다.");
            //rester::set_response_code(400);
            //rester::error('METHOD 형식이 잘못되었습니다.');
        }

        // check allows ip address
        if($cfg['access_control']['allows_origin']!='*')
        {
            if(!is_array($cfg['access_control']['allows_origin']))
            {
                $cfg['access_control']['allows_origin'] = array($cfg['access_control']['allows_origin']);
            }

            if(!in_array(GetRealIPAddr(),$cfg['access_control']['allows_origin']))
            {
                throw new ExceptionBase("접근권한이 없습니다.");
                //rester::set_response_code(401);
                //rester::error('접근권한이 없습니다.');
            }
        }

        // request header 값 설정
        // config 에 허용된 헤더만 받음
        $cfg['request-headers'] = array();
        foreach (getallheaders() as $key => $value)
        {
            if (preg_grep('/' . $key . '/i', $cfg['access_control']['allows_headers']))
            {
                $cfg['request-headers'][$key] = $value;
            }
        }

        // request parameter 값 설정
        $cfg['request-body'] = array();
        if($body = json_decode(file_get_contents('php://input'),true))
        {
            $cfg['request-body'] = $body;
        }
        else
        {
            $cfg['request-body'] = $_POST;
            unset($_POST);
        }

        self::$data = $cfg;
    }

    /**
     * 환경설정값 반환
     *
     * @param string $section
     * @param string $key
     *
     * @return mixed
     */
    public static function Get($section='', $key='')
    {
        if(!isset(self::$data))
        {
            try
            {
                self::init();
            }
            catch (ExceptionBase $e)
            {
                echo $e;
            }
        }
        if($section==='') return self::$data;
        if($section && $key) return self::$data[$section][$key];
        return self::$data[$section];
    }
}
