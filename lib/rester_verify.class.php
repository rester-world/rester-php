<?php

/**
 * Class rester_verify
 */
class rester_verify
{
    const TYPE = 'type';
    const TYPE_REGEX = 'regexp';
    const TYPE_FUNCTION = 'function';
    const TYPE_FILTER = 'filter';
    const TYPE_TOKEN = 'token';

    const OPTIONS = 'options';

    const REGEXP = 'regexp';
    const DEFAULT = 'default';
    const REQUIRE = 'require';

    const TOKEN = 'token';

    /**
     * @var array
     */
    protected $filter = [
        self::TOKEN=>[
            self::TYPE=>self::TYPE_TOKEN
        ]
    ];

    /**
     * @var array
     */
    protected $result;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $proc;

    /**
     * @var string
     */
    protected $method;

    /**
     * rester_verify constructor.
     *
     * @param string $module
     * @param string $proc
     * @param string $method
     *
     * @throws Exception
     */
    public function __construct($module,$proc,$method)
    {
        $this->module = $module;
        $this->proc = $proc;
        $this->method = $method;
        $this->result = [];

        // 사용자 함수 있으면 추가
        if($path_user_function = $this->path_user_func())
        {
            include $path_user_function;
        }

        // init
        if($path = $this->path())
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_RAW);
            foreach($cfg as $k=>$v)
            {
                foreach($v as $kk=>$vv)
                {
                    $this->filter[$k][$kk] = $vv;
                }
            }
        }

        // filter 검증
        foreach ($this->filter as $k=>$v)
        {
            // 필드타입에 따라 옵션으로 필수로 받는 내용이 달라진다.
            switch ($v[self::TYPE])
            {
                case self::TYPE_REGEX: if(!isset($v[self::TYPE_REGEX])) throw new Exception("Required parameter.[regexp]"); break;
                case self::TYPE_FILTER: if(!isset($v[self::TYPE_FILTER])) throw new Exception("Required parameter.[filter]"); break;
                case self::TYPE_FUNCTION: break;
                default:
                    $func = 'validate_' . $v['type'];
                    if (!method_exists($this, $func)) throw new Exception("Not supported type. ({$v['type']})");
            }
        }
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     */
    protected function path()
    {
        $path = implode('/',array(
            dirname(__FILE__).'/../'.rester::path_module,
            $this->module,
            $this->proc,
            $this->method.'.ini'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     */
    protected function path_user_func()
    {
        $path = implode('/',array(
            dirname(__FILE__).'/../../'.rester::path_module,
            $this->module,
            $this->proc,
            $this->method.'.verify.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     *
     * @param null|string $key
     * @return bool|mixed
     */
    public function param($key=null)
    {
        if(isset($this->result[$key])) return $this->result[$key];
        if($key == null) return $this->result;
        return false;
    }

    /**
     * verify request parameter
     *
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function validate($data)
    {
        // reset result
        $this->result = [];

        // check param
        if(!is_array($data)) return [];

        // 연관배열 검사
        if(sizeof($data)>0)
        {
            $keys = array_keys($data);
            if(!is_array($data) || (array_keys($keys) === $keys)) throw new Exception("Invalid parameter.(associative array)");
        }

        foreach($this->filter as $k=>$v)
        {
            $schema = $v;

            // 기본값
            $result = false;
            if(isset($v[self::DEFAULT])) $result = $v[self::DEFAULT];

            $type = $v[self::TYPE];
            if($data[$k]!==null)
            {
                switch ($type)
                {
                    // Using Regular Expressions : preg_match
                    case self::TYPE_REGEX:
                        if (preg_match($schema[self::REGEXP], $data[$k], $matches))
                            $result = $matches[0];
                        break;

                    // php validate function
                    // filter_val
                    case self::TYPE_FILTER:

                        $filter = null;
                        $options = null;
                        eval("\$filter = " . $schema[self::TYPE_FILTER] . ";");
                        if($schema[self::OPTIONS]) eval("\$options = " . $schema[self::OPTIONS] . ";");

                        if(!is_integer($filter)) throw new Exception($k.'='.$data[$k]." : Invalid filter format.");
                        if($options !== null && !is_integer($options)) throw new Exception($k.'='.$data[$k]." : Filter option format is invalid.");
                        if (false !== ($clean = filter_var($data[$k], $filter, $options))) $result = $clean;
                        break;

                    // User Define Function
                    // 사용자 정의 함수는 호출 가능할 때만 실행
                    case self::TYPE_FUNCTION:
                        $func = $k;
                        if (is_callable($func))
                        {
                            $result = $func($data[$k]);
                        }
                        break;

                    // rester define function
                    // 필터 오류시 warning 으로
                    default:
                        $func = 'validate_' . $schema[self::TYPE];
                        if (method_exists($this, $func))
                        {
                            try
                            {
                                $result = $this->$func($data[$k]);
                            }
                            catch(Exception $e)
                            {
                                rester_response::warning($e->getMessage());
                            }
                        }
                        else throw new Exception($k.'='.$data[$k]." : There is no Rester definition function.");
                }
            }

            // 필수입력 체크
            $require = $v[self::REQUIRE]=='true'?true:false;
            if($require && !$result)
            {
                throw new Exception($k." : The required input data does not have a value or pass validation.");
            }
            $this->result[$k] = $result;
        }
        return $this->result;
    }

    // get verified param

    /**
     * @param string $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_id($data)
    {
        if(preg_match('/^[a-zA-Z][a-zA-Z0-9_\-:.]*$/', $data, $matches)) return $data;
        throw new Exception("Invalid data(id) : {$data} (a-z, A-z, 0-9, -, _, :, .)");
    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    protected function validate_bool($data)
    {
        if(is_bool($data) || $data==0 || $data==1) return $data;
        throw new Exception("Invalid data(bool) : {$data}");
    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    protected function validate_boolean($data)
    {
        return $this->validate_bool($data);
    }

    /**
     * 날짜 형식 채크
     *
     * @param string $data
     *
     * @return bool|string
     * @throws Exception
     */
    protected function validate_datetime($data)
    {
        $parsed = date_parse($data);
        if($parsed['error_count']===0) return $data;
        throw new Exception("Invalid data(datetime) : {$data}");
    }

    /**
     * 날짜 형식 체크
     *
     * @param string $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_date($data)
    {
        $parsed = date_parse($data);
        if(
            $parsed['error_count']===0 &&
            $parsed['year']!==false && $parsed['month']!==false && $parsed['day']!==false &&
            $parsed['hour']===false && $parsed['minute']===false && $parsed['second']===false
        )
            return $data;
        throw new Exception("Invalid data(date) : {$data}");
    }

    /**
     * 시간형식 체크
     *
     * @param string $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_time($data)
    {
        $parsed = date_parse($data);
        if(
            $parsed['error_count']===0 &&
            $parsed['year']===false && $parsed['month']===false && $parsed['day']===false &&
            $parsed['hour']!==false && $parsed['minute']!==false && $parsed['second']!==false
        )
            return $data;
        throw new Exception("Invalid data(time) : {$data}");
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    protected function validate_array($data)
    {
        if(is_array($data)) return $data;
        throw new Exception("Invalid data(array) : {$data}");
    }


    /**
     * 파일명 검증
     * 파일명에 쓸 수 없는 9가지 문자가 있으면 안됨
     * \ / : * ? " < > |
     *
     * @param string $data
     *
     * @return null|string|string[]
     * @throws Exception
     */
    protected function validate_filename($data)
    {
        if(preg_match('/[\\/:\*\?\"<>\|]/', $data, $matches)) throw new Exception("Invalid data(filename) : {$data}");
        return $data;
    }

    /**
     * @param $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_token($data)
    {
        if(preg_match('/^[0-9a-zA-Z.]+$/', $data, $matches)) return $data;
        throw new Exception("Invalid data(token) : {$data}");
    }

    /**
     * @param $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_module($data)
    {
        if(preg_match('/^[a-zA-Z][0-9a-zA-Z_-]*$/', $data, $matches)) return $data;
        throw new Exception("Invalid data(module name) : {$data}");
    }

    /**
     * @param $data
     *
     * @return int
     * @throws Exception
     */
    protected function validate_key($data)
    {
        if(preg_match('/^[1-9][0-9]*$/', $data, $matches)) return intval($data);
        throw new Exception("Invalid data(key) : {$data}");
    }

    /**
     * @param $data
     *
     * @return int
     * @throws Exception
     */
    protected function validate_number($data)
    {
        if(preg_match('/^[0-9]+$/', $data, $matches)) return intval($data);
        throw new Exception("Invalid data(number) : {$data}");
    }

    /**
     * @param $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_mime($data)
    {
        if(preg_match('/^[0-9a-zA-z\/\.\-\_]+$/', $data, $matches)) return $data;
        throw new Exception("Invalid data(mime) : {$data}");
    }

    /**
     * @param $data
     *
     * @return string
     */
    protected function validate_string($data)
    {
        return filter_var($data,FILTER_SANITIZE_STRING);
    }

    /**
     * @param $data
     *
     * @return string
     * @throws Exception
     */
    protected function validate_json($data)
    {
        if(@json_decode(stripslashes($data),true)) return $data;
        else throw new Exception("Invalid data(json) : {$data}");
    }

    /**
     * @param $data
     *
     * @return string
     */
    protected function validate_url($data)
    {
        return filter_var($data,FILTER_VALIDATE_URL);
    }

    /**
     * @param $data
     *
     * @return string
     */
    protected function validate_email($data)
    {
        return filter_var($data,FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param $data
     *
     * @return string
     */
    protected function validate_html($data)
    {
        return $data;
    }
}
