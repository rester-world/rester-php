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
 * 외부 모듈 호출
 *
 * @param string $name
 * @param string $module
 * @param string $proc
 * @param array  $param
 *
 * @return bool|array
 */
function request($name, $module, $proc, $param=[])
{
    try
    {
        $cfg = cfg::request($name);
        if(!$cfg || !$cfg[cfg::request_host] || !$cfg[cfg::request_port]) throw new Exception("There is no config.({$name})");
        if(!$module) throw new Exception("\$module is a required input.");
        if(!$proc) throw new Exception("\$proc is a required input.");
        $url = implode('/',array( $cfg['host'].':'.$cfg['port'], 'v1', $module, $proc ));

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($param),
        ));

        $response_body = curl_exec($ch);
        curl_close($ch);
        return json_decode($response_body,true);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
        return false;
    }
}
