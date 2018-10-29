<?php
namespace Rester\Data;
use \Rester\Exception\ExceptionBase;
/**
 * Class Schema
 * kevinpark@webace.co.kr
 *
 * 스키마 정의를 받아서 validation을 수행해 줌
 *
 */
class Schema
{
    const FIELD_TYPE = 'type';
    const FIELD_REQUIRE = 'require';
    const FIELD_DEFAULT = 'default';
    const FIELD_REGEXP = 'regexp';
    const FIELD_OPTIONS = 'options';

    const TYPE_REGEX = 'regexp';
    const TYPE_FUNCTION = 'function';
    const TYPE_FILTER = 'filter';
    const TYPE_FILENAME = 'filename';
    const TYPE_ID = 'id';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';
    const TYPE_ARRAY = 'array';

    /**
     * @var array 지원되는 타입 목록
     */
    private $types = array(
        self::TYPE_REGEX,
        self::TYPE_FUNCTION,
        self::TYPE_FILENAME,
        self::TYPE_ID,
        self::TYPE_DATETIME,
        self::TYPE_DATE,
        self::TYPE_TIME,
        self::TYPE_ARRAY,
    );

    private $schema = array();

    /**
     * @param string $data
     *
     * @return string
     * @throws ExceptionBase
     */
    protected function validate_id($data)
    {
        if(preg_match('/^[a-zA-Z][a-zA-Z0-9_\-:.]*$/', $data, $matches)) return $data;
        throw new ExceptionBase("아이디에 허용되지 않은 문자가 있습니다. 허용문자(영문, 숫자, -, _, :, .)");
    }

    /**
     * 날짜 형식 채크
     *
     * @param string $data
     *
     * @return bool|string
     * @throws ExceptionBase
     */
    protected function validate_datetime($data)
    {
        $parsed = date_parse($data);
        if($parsed['error_count']===0) return $data;
        throw new ExceptionBase("날짜/시간 형식이 잘못되었습니다.");
    }

    /**
     * 날짜 형식 체크
     *
     * @param string $data
     *
     * @return string
     * @throws ExceptionBase
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
        throw new ExceptionBase("날짜 형식이 맞지 않습니다.");
    }

    /**
     * 시간형식 체크
     *
     * @param string $data
     *
     * @return string
     * @throws ExceptionBase
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
        throw new ExceptionBase("시간 형식이 맞지 않습니다.");
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws ExceptionBase
     */
    protected function validate_array($data)
    {
        if(is_array($data)) return $data;
        throw new ExceptionBase("배열이 아닙니다.");
    }


    /**
     * 파일명 검증
     * 파일명에 쓸 수 없는 9가지 문자가 있으면 안됨
     * \ / : * ? " < > |
     *
     * @param string $data
     *
     * @return null|string|string[]
     * @throws ExceptionBase
     */
    protected function validate_filename($data)
    {
        if(preg_match('/[\\/:\*\?\"<>\|]/', $data, $matches)) throw new ExceptionBase("파일명에는 특수문자가 올 수 없습니다.");
        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws ExceptionBase
     */
    public function validate($data)
    {
        // check param
        if(is_array($data) && sizeof($data)==0) return array();
        if(!is_array($data) || !is_assoc($data)) throw new ExceptionBase("The first parameter requires an associative array.");

        $result = array();

        foreach ($data as $k=>$v)
        {
            if(!($schema = $this->schema[$k])) continue;
            $type = $schema[self::FIELD_TYPE];
            $require = $schema[self::FIELD_REQUIRE]=='true'?true:false;
            // set default value
            if(isset($schema[self::FIELD_DEFAULT])) $result[$k] = $schema[self::FIELD_DEFAULT];

            switch ($type)
            {
                // Using Regular Expressions : preg_match
                case self::TYPE_REGEX: if (preg_match($schema['regexp'], $v, $matches)) $result[$k] = $matches[0]; break;

                // php validate function
                // filter_val
                case self::TYPE_FILTER:

                    $filter = null;
                    $options = null;
                    eval("\$filter = " . $schema[self::TYPE_FILTER] . ";");
                    if($schema[self::FIELD_OPTIONS]) eval("\$options = " . $schema[self::FIELD_OPTIONS] . ";");

                    if(!is_integer($filter)) throw new ExceptionBase($k.'='.$v." : Invalid filter format.");
                    if($options !== null && !is_integer($options)) throw new ExceptionBase($k.'='.$v." : Filter option format is invalid.");
                    if (false !== ($clean = filter_var($v, $filter, $options))) $result[$k] = $clean;
                    break;

                // User Define Function
                // 사용자 정의 함수는 호출 가능할 때만 실행
                case self::TYPE_FUNCTION:
                    $func = $schema[self::TYPE_FUNCTION];
                    if (is_callable($func) && ($clean = $func($v))) $result[$k] = $clean;
                    break;

                // rester define function
                default:
                    $func = 'validate_' . $this->schema[$k]['type'];
                    if (method_exists($this, $func)) $result[$k] = $this->$func($v);
                    else throw new ExceptionBase($k.'='.$v." : There is no Rester definition function.");
            }

            // Check Require value
            if(!isset($result[$k]) && $require)
            {
                throw new ExceptionBase($k." : The required input data does not have a value or pass validation.");
            }

        }
        return $result;
    }

    /**
     * 필수입력 데이터 검사
     * 하나라도 누락되면 Exception을 반환한다.
     *
     * @param array $data
     *
     * @throws ExceptionBase
     */
    public function check_require($data)
    {
        foreach($this->schema as $k=>$v)
        {
            if($v['require']=='true')
            {
                // null 또는 공백일경우
                if($data[$k]!==0 && !$data[$k])
                {
                    throw new ExceptionBase($k." : 필수입력 데이터가 누락되었습니다.");
                }
            }
        }
    }


    /**
     * Schema constructor.
     *
     * @param mixed $schema json string | json file | array |.ini file path
     *
     * @throws ExceptionBase
     */
    public function __construct($schema)
    {
        try
        {
            // array data
            if(is_array($schema))
            {
                $this->set_schema($schema);
            }
            // ini,json file
            elseif(is_file($schema))
            {
                $ext = array_pop(explode('.',$schema));
                if($ext == 'ini') $this->set_schema_file_ini($schema);
                elseif($ext == 'json') $this->set_schema_file_json($schema);
                else throw new ExceptionBase("파일형식을 확인해 주세요.");
            }
            // json string
            elseif(($data = json_decode($schema,true)) && (json_last_error() == JSON_ERROR_NONE))
            {
                $this->set_schema_json($schema);
            }
            // error : not support
            else
            {
                throw new ExceptionBase("스키마 지원 형식을 확인해 주세요.");
            }
        }
        catch (ExceptionBase $e)
        {
            throw $e;
        }
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
     * @throws ExceptionBase
     */
    protected function set_schema($data)
    {
        // 배열 형식 검사
        if(!is_array($data)) throw new ExceptionBase("데이터는 배열형식 이어야 합니다.");

        foreach ($data as $k=>$v)
        {
            // 키값 형식 검사
            if(!is_string($k)) throw new ExceptionBase("필드 키값은 문자열입니다.");

            // 필드타입에 따라 옵션으로 필수로 받는 내용이 달라진다.
            switch ($v['type'])
            {
                case self::TYPE_REGEX:
                    if(!isset($v[self::TYPE_REGEX])) throw new ExceptionBase("[regexp = 정규식] 필수 사항입니다.");
                    break;

                case self::TYPE_FILTER:
                    if(!isset($v[self::TYPE_FILTER])) throw new ExceptionBase("[filter = 필터명] 필수 사항입니다.");
                    break;

                default:
                    if(!in_array($v['type'], $this->types)) throw new ExceptionBase("지원되지 않는 type 값입니다. ({$v['type']})");
            }
        }
        $this->schema = $data;
    }

    /**
     * insert json string schema
     *
     * @param string $json_string
     *
     * @throws ExceptionBase
     */
    protected function set_schema_json($json_string)
    {
        try
        {
            $this->set_schema(json_decode($json_string,true));
        }
        catch (ExceptionBase $e)
        {
            throw $e;
        }
    }

    /**
     * insert json file schema
     *
     * @param string $file_path
     *
     * @throws ExceptionBase
     */
    protected function set_schema_file_json($file_path)
    {
        try
        {
            $this->set_schema(json_decode(file_get_contents($file_path),true));
        }
        catch (ExceptionBase $e)
        {
            throw $e;
        }
    }

    /**
     * insert ini file schema
     *
     * @param string $file_path
     *
     * @throws ExceptionBase
     */
    protected function set_schema_file_ini($file_path)
    {
        try
        {
            $this->set_schema(parse_ini_file($file_path,true, INI_SCANNER_RAW));
        }
        catch (ExceptionBase $e)
        {
            throw $e;
        }
    }

    /**
     * anonymous function
     *
     * @param string $key
     * @param callable $function
     *
     * @throws ExceptionBase
     */
    public function set_schema_func($key, $function)
    {
        if(is_callable($function)) $this->schema[$key]['function'] = $function;
        else throw new ExceptionBase("2번째 파라미터는 호출 가능한 함수여야 합니다.");
    }
}
