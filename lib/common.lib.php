<?php if(!defined('__RESTER__')) exit;

/**
 * Check associative array
 * 연관 배열인지 검사
 * 숫자가 아닌 키값이 하나라도 있으면 연관배열로 추정
 *
 * @param array $arr
 * @return bool
 */
function is_assoc($arr)
{
    $res = false;
    foreach($arr as $k=>$v) if(!is_numeric($k)) $res = true;
    return $res;
}

/**
 * 사용중인 rester instance
 */
$current_rester = null;

/**
 * return analyzed parameter
 *
 * @param null|string $key
 * @return bool|mixed
 */
function request_param($key=null)
{
    global $current_rester;
    return $current_rester->request_param($key);
}

/**
 * @param string $module
 * @param string $proc
 * @param string $method
 * @param array  $query
 *
 * @return mixed
 */
function request_module($module, $proc, $method, $query=[])
{
    global $current_rester;
    $old_rester = $current_rester;
    $res = false;

    try
    {
        $current_rester = new rester($module, $proc, $method, $query);
        $res = $current_rester->run($old_rester);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
    }

    $current_rester = $old_rester;
    return $res;
}

/**
 * @param string $proc
 * @param string $method
 * @param array  $query
 *
 * @return mixed
 */
function request_procedure($proc, $method, $query=[])
{
    global $current_rester;
    $old_rester = $current_rester;
    $res = false;

    try
    {
        $current_rester = new rester($current_rester->module(), $proc, $method, $query);
        $res = $current_rester->run($old_rester);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
    }

    $current_rester = $old_rester;
    return $res;
}

/**
 * 외부 서비스 호출
 *
 * @param string $method
 * @param string $name
 * @param string $module
 * @param string $proc
 * @param array  $param
 *
 * @return bool|array
 */
function exten($method, $name, $module, $proc, $param=[])
{
    $result = false;
    $cfg = cfg::request($name);

    try
    {
        if(!($method=='POST' || $method=='GET')) throw new Exception("Allowed \$method [POST|GET].",rester_response::code_request_method);
        if(!$module) throw new Exception("\$module is a required input.",rester_response::code_parameter);
        if(!$proc) throw new Exception("\$proc is a required input.",rester_response::code_parameter);

        if(
            !$cfg ||
            !$cfg[cfg::request_host] ||
            !$cfg[cfg::request_port] ||
            !$cfg[cfg::request_prefix]
        )
            throw new Exception("There is no config.(cfg[request][{$name}])",rester_response::code_config);

        $url = implode('/', [
            $cfg[cfg::request_host].':'.$cfg[cfg::request_port],
            $cfg[cfg::request_prefix],
            $module,
            $proc
        ]);

        if($method=='GET')
        {
            $query = [];
            foreach($param as $key=>$value)
            {
                $query[] = $key.'='.$value;
            }
            $url .= '?'.urlencode(implode('&',$query));
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method
        ));
        if($method=='POST') curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($param));

        $response_body = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response_body,true);
    }
    catch (Exception $e)
    {
        rester_response::failed($e->getCode(),$e->getMessage());
        rester_response::error_trace(explode("\n",$e->getTraceAsString()));
    }
    return $result;
}

/**
 * 외부 서비스 호출
 *
 * @param string $name
 * @param string $module
 * @param string $proc
 * @param array  $param
 *
 * @return bool|array
 */
function exten_get($name, $module, $proc, $param=[])
{
    return exten('GET',$name,$module,$proc,$param);
}

/**
 * 외부 서비스 호출
 *
 * @param string $name
 * @param string $module
 * @param string $proc
 * @param array  $param
 *
 * @return bool|array
 */
function exten_post($name, $module, $proc, $param=[])
{
    return exten('POST',$name,$module,$proc,$param);
}

/**
 * @param array|mixed $res
 *
 * @return array|bool|mixed
 */
function response_data($res)
{
    $data = false;
    if($res['success'])
    {
        if(is_array($res['data']) && sizeof($res['data'])==1) $data = $res['data'][0];
        else $data = $res['data'];
    }
    else
    {
        rester_response::failed(rester_response::code_response_fail, implode('/',$res['error']));
    }
    return $data;
}
