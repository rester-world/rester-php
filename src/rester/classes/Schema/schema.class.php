<?php
namespace Rester\Schema;
/**
 * Class schema
 * kevinpark@webace.co.kr
 *
 * 스키마 정의를 받아서 validation을 수행해 줌
 *
 */
class schema
{
    const TYPE_REGEX = 'regexp';
    const TYPE_FUNCTION = 'function';
    const TYPE_FILTER = 'filter';
    const TYPE_FILENAME = 'filename';
    const TYPE_STRING = 'string';
    const TYPE_DATETIME = 'datetime';

    private $types = array(
        self::TYPE_REGEX,
        self::TYPE_FUNCTION,
        self::TYPE_FILENAME,
        self::TYPE_STRING,
        self::TYPE_DATETIME,
    );

    private $schema = array();

    protected function validate_string($data)
    {

    }

    protected function validate_datetime($data)
    {

    }


    protected function validate_filename($data)
    {

    }

    public function validate($data, $strict=false)
    {
        // check param
        if(!is_array($data)) throw new schemaException("1번째 파라미터는 연관배열이 필요합니다.", schemaException::ERR_PARAM);
        if(!is_bool($strict)) throw new schemaException("2번째 파라미터는 boolean  필요합니다.", schemaException::ERR_PARAM);

        $result = array();

        foreach ($data as $k=>$v)
        {
            if (!isset($this->schema[$k]))
            {
                if ($strict) throw new schemaException("스키마에 없는 필드입니다.", schemaException::ERR_NO_FIELD);
                continue;
            }

            $schema = $this->schema[$k];
            $type = $schema['type'];

            switch ($type)
            {
                // 정규표현식 사용
                case self::TYPE_REGEX:
                    if (preg_match($schema['regexp'], $v, $matches)) $result[$k] = $matches[0];
                    elseif ($strict) throw new schemaException("데이터가 정규표현식과 맞지 않습니다.", schemaException::ERR_VALIDATE_DATA);
                    break;

                // PHP 기본함수 사용
                // php validate function
                // eval 로 문자열로 된 옵션을 실제 값으로 변경
                case self::TYPE_FILTER:

                    $filter = null;
                    $options = null;
                    eval("\$filter = " . $schema[self::TYPE_FILTER] . ";");
                    if($schema['options']) eval("\$options = " . $schema['options'] . ";");

                    if(!is_integer($filter)) throw new schemaException("필터 형식이 잘못되었습니다.", schemaException::ERR_FILTER_TYPE);
                    if($options !== null && !is_integer($options)) throw new schemaException("필터 옵션 형식이 잘못되었습니다.", schemaException::ERR_FILTER_TYPE);

                    if ($data = filter_var($data, $filter, $options))
                    {
                        $result[$k] = $data;
                    }
                    elseif ($strict)
                    {
                        throw new schemaException("데이터가 필터를 통과하지 못했습니다.", schemaException::ERR_VALIDATE_DATA);
                    }
                    break;

                case self::TYPE_FUNCTION:
                    $func = $this->schema[$k][self::TYPE_FUNCTION];
                    if (!is_callable($func)) throw new schemaException("호출가능한 함수를 등록하세요.", schemaException::ERR_VALIDATE_DATA);

                    if ($data = $func($v))
                    {
                        $result[$k] = $data;
                    }
                    elseif ($strict)
                    {
                        throw new schemaException("데이터가 사용자정의 함수를 통과하지 못했습니다.", schemaException::ERR_VALIDATE_DATA);
                    }
                    break;

                default:
                    $func = 'validate_' . $this->schema[$k]['type'];
                    if (method_exists($this, $func))
                    {
                        $result[$k] = $this->$func($v);
                    }
                    else
                    {
                        throw new schemaException("함수가 정의되어 있지 않습니다.", schemaException::ERR_VALIDATE_DATA);
                    }
            }

        }
        return $result;
    }


    /**
     * schema constructor.
     *
     * @param mixed $schema json string | json file | array |.ini file path
     * @throws Exception
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
                else throw new schemaException("파일형식을 확인해 주세요.", schemaException::ERR_FILE_TYPE);
            }
            // json string
            elseif(($data = json_decode($schema,true)) && (json_last_error() == JSON_ERROR_NONE))
            {
                $this->set_schema_json($schema);
            }
            // error : not support
            else
            {
                throw new schemaException("스키마 지원 형식을 확인해 주세요.", schemaException::ERR_FORMAT);
            }
        }
        catch (schemaException $e)
        {
            throw $e;
        }
    }

    /**
     * 스키마를 설정함
     *
     * 스키마구조
     * ----------
     * key[type] 필수
     * key[regexp] 정규식 (type=regexp)
     * key[filter] integer php 함수의 필터값 (type=filter)
     * key[option] integer php 함수의 옵션값 (type=filter)
     *
     * @param array $data
     * @throws schemaException
     */
    public function set_schema($data)
    {
        // 배열 형식 검사
        if(!is_array($data)) throw new schemaException("데이터는 배열형식 이어야 합니다.",schemaException::ERR_CONTENT);

        foreach ($data as $k=>$v)
        {
            // 키값 형식 검사
            if(!is_string($k)) throw new schemaException("필드 키값은 문자열입니다.",schemaException::ERR_CONTENT);

            // 필드타입에 따라 옵션으로 필수로 받는 내용이 달라진다.
            switch ($v['type'])
            {
                case self::TYPE_REGEX:
                    if(!isset($v[self::TYPE_REGEX])) throw new schemaException("[regexp = 정규식] 필수 사항입니다.",schemaException::ERR_CONTENT);
                    break;
                case self::TYPE_FILTER:
                    if(!isset($v[self::TYPE_FILTER])) throw new schemaException("[filter = 필터명] 필수 사항입니다.",schemaException::ERR_CONTENT);
                    break;
                default:
                    if(!in_array($v['type'], $this->types)) throw new schemaException("지원되지 않는 type 값입니다. ({$v['type']})",schemaException::ERR_CONTENT);
            }
        }
        $this->schema = $data;
    }

    /**
     * insert json string schema
     *
     * @param string $json_string
     * @throws schemaException
     */
    public function set_schema_json($json_string)
    {
        try
        {
            $this->set_schema(json_decode($json_string,true));
        }
        catch (schemaException $e)
        {
            throw $e;
        }
    }

    /**
     * insert json file schema
     *
     * @param string $file_path
     * @throws schemaException
     */
    public function set_schema_file_json($file_path)
    {
        try
        {
            $this->set_schema(json_decode(file_get_contents($file_path),true));
        }
        catch (schemaException $e)
        {
            throw $e;
        }
    }

    /**
     * insert ini file schema
     *
     * @param string $file_path
     * @throws schemaException
     */
    public function set_schema_file_ini($file_path)
    {
        try
        {
            $this->set_schema(parse_ini_file($file_path,true, INI_SCANNER_TYPED));
        }
        catch (schemaException $e)
        {
            throw $e;
        }
    }

    /**
     * regist anonymous function
     *
     * @param string $key
     * @param callable $function
     * @throws schemaException
     */
    public function set_schema_func($key, $function)
    {
        if(is_callable($function)) $this->schema[$key]['function'] = $function;
        else throw new schemaException("2번째 파라미터는 호출 가능한 함수가 여야 합니다.", schemaException::ERR_FUNCTION_TYPE);
    }
}
