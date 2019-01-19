<?php
/**
 * Class Schema
 * kevinpark@webace.co.kr
 *
 * 스키마 정의를 받아서 validation 을 수행해 줌
 *
 * 주의사항: 오류사항 발생시 항상 Exception 처리를 해 준다
 *
 */
class schema
{
    const FIELD_TYPE = 'type';
    const FIELD_REQUIRE = 'require';
    const FIELD_DEFAULT = 'default';
    const FIELD_REGEXP = 'regexp';
    const FIELD_OPTIONS = 'options';

    const TYPE_REGEX = 'regexp';
    const TYPE_FUNCTION = 'function';
    const TYPE_FILTER = 'filter';

    private $schema = array('token'=>array('type'=>'token'));

    /**
     * Schema constructor.
     *
     * @param string $schema json file | .ini file path
     *
     * @throws Exception
     */
    public function __construct($schema)
    {
        try
        {
            // ini,json file
            if(is_file($schema))
            {
                $ext = array_pop(explode('.',$schema));
                if($ext == 'ini') $this->set_schema_file_ini($schema);
                elseif($ext == 'json') $this->set_schema_file_json($schema);
                else throw new Exception("schema - Not supported file format.");
            }
            // error : not support
            else
            {
                throw new Exception("schema - Not supported schema format.(.ini, .json)");
            }
        }
        catch (Exception $e) { throw $e; }
    }

    /**
     * insert json file schema
     *
     * @param string $file_path
     *
     * @throws Exception
     */
    protected function set_schema_file_json($file_path)
    {
        $this->set_schema(json_decode(file_get_contents($file_path),true));
    }

    /**
     * insert ini file schema
     *
     * @param string $file_path
     *
     * @throws Exception
     */
    protected function set_schema_file_ini($file_path)
    {
        $this->set_schema(parse_ini_file($file_path,true, INI_SCANNER_RAW));
    }

    /**
     * 스키마를 설정함
     *
     * @param array $data
     *
     * 스키마구조
     * ----------
     * key[type] 필수
     * key[regexp] 정규식 (type=regexp)
     * key[filter] integer php 함수의 필터값 (type=filter)
     * key[options] integer php 함수의 옵션값 (type=filter)
     *
     * @throws Exception
     */
    protected function set_schema($data)
    {
        // check parameter
        if(!is_array($data)) throw new Exception("Invalid parameter.(array)");

        foreach ($data as $k=>$v)
        {
            // 필드타입에 따라 옵션으로 필수로 받는 내용이 달라진다.
            switch ($v['type'])
            {
                case self::TYPE_REGEX: if(!isset($v[self::TYPE_REGEX])) throw new Exception("Required parameter.[regexp]"); break;
                case self::TYPE_FILTER: if(!isset($v[self::TYPE_FILTER])) throw new Exception("Required parameter.[filter]"); break;
                case self::TYPE_FUNCTION: break;
                default:
                    $func = 'validate_' . $v['type'];
                    if (!method_exists($this, $func)) throw new Exception("Not supported type. ({$v['type']})");
            }
        }
        $data['token'] = array('type'=>'token');
        $this->schema = $data;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function validate($data)
    {
        // check param
        if(!is_array($data)) return array();

        if(sizeof($data)>0)
        {
            $keys = array_keys($data);
            if(!is_array($data) || (array_keys($keys) === $keys)) throw new Exception("Invalid parameter.(associative array)");
        }

        $result = array();

        foreach($this->schema as $k=>$v)
        {
            $schema = $v;
            $default = false;
            if(isset($v[self::FIELD_DEFAULT])) $default = $v[self::FIELD_DEFAULT];
            $require = $v[self::FIELD_REQUIRE]=='true'?true:false;

            $type = $v[self::FIELD_TYPE];

            $_result = $default;

            if($data[$k])
            {
                switch ($type)
                {
                    // Using Regular Expressions : preg_match
                    case self::TYPE_REGEX:
                        if (preg_match($schema[self::FIELD_REGEXP], $data[$k], $matches))
                            $_result = $matches[0];
                        break;

                    // php validate function
                    // filter_val
                    case self::TYPE_FILTER:

                        $filter = null;
                        $options = null;
                        eval("\$filter = " . $schema[self::TYPE_FILTER] . ";");
                        if($schema[self::FIELD_OPTIONS]) eval("\$options = " . $schema[self::FIELD_OPTIONS] . ";");

                        if(!is_integer($filter)) throw new Exception("Invalid filter format.({$k}={$data[$k]})");
                        if($options !== null && !is_integer($options)) throw new Exception("Filter option format is invalid.({$k}={$data[$k]})");
                        if (false !== ($clean = filter_var($data[$k], $filter, $options))) $_result = $clean;
                        break;

                    // User Define Function
                    // 사용자 정의 함수는 호출 가능할 때만 실행
                    case self::TYPE_FUNCTION:
                        $func = $k;
                        if (is_callable($func) && ($clean = $func($data[$k]))) $_result = $clean;
                        break;

                    // rester define function
                    default:
                        $func = 'validate_' . $schema[self::FIELD_TYPE];
                        if (method_exists($this, $func)) $_result = $this->$func($data[$k]);
                        else throw new Exception("There is no filter function.({$k}={$data[$k]})");
                }
            }

            if($require && !$_result)
            {
                throw new Exception($k." : The required input data does not have a value or pass validation.");
            }
            $result[$k] = $_result;
        }

        return $result;
    }

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
     * @throws Exception
     */
    protected function validate_string($data)
    {
        return filter_var($data,FILTER_SANITIZE_STRING);
    }

    /**
     * @param $data
     *
     * @return string
     */
    protected function validate_json($data)
    {
        $ret = false;
        if(@json_decode($data,true)) $ret = $data;
        return $ret;
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
    protected function validate_html($data)
    {
        return $data;
    }
}

