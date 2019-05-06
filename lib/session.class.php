<?php
/**
 * Class session
 * kevinpark@webace.co.kr
 */
class session
{
    private static $session_id;	// 세션 아이디
    /**
     * @var Redis
     */
    private static $cache;

    /**
     * 토큰생성
     *
     * @param int $length
     *
     * @return string token
     */
    public static function gen_token($length=40)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.!@#$%^&()-_*=+';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, strlen($characters))];
        }
        return $token;
    }

    /**
     * @throws Exception
     */
    protected static function connect_cache()
    {
        if(self::$cache) return;

        $redis_cfg = cfg::cache();
        if(!($redis_cfg['host'] && $redis_cfg['port']))
            throw new Exception("Require cache config to use auth.", rester_response::code_config);

        self::$cache = new Redis();
        if(self::$cache->connect($redis_cfg['host'], $redis_cfg['port'], 1.0))
        {
            if ($redis_cfg['auth']) self::$cache->auth($redis_cfg['auth']);
        }
        else
        {
            throw new Exception("Can not access redis server.", rester_response::code_cache_server);
        }
    }

    /**
     * @param string $token
     *
     * @return bool|string
     * @throws Exception
     */
    public static function get_token($token)
    {
        self::connect_cache();
        if(self::$session_id = self::$cache->get('token_'.$token))
        {
            if(json_decode(self::$session_id,true))
            {
                self::$session_id = json_decode(self::$session_id,true);
            }
            return self::$session_id;
        }
    }

    /**
     * @param mixed $data
     *
     * @return string
     * @throws Exception
     */
    public static function set_token($data)
    {
        if(!$data) throw new Exception("Require first parameter.", rester_response::code_parameter);
        if(is_array($data)) $data = json_encode($data);

        self::connect_cache();
        $timeout = intval(cfg::Get('session','timeout'));
        do {
            $token = self::gen_token();
        } while(self::$cache->get('token_'.$token));

        self::$cache->set('token_'.$token,$data,$timeout);
        self::$session_id = $data;
        return $token;
    }

    /**
     * @param string $key
     * @param mixed  $data
     * @param int $timeout
     *
     * @throws Exception
     */
    public static function set($key,$data,$timeout)
    {
        if(!$key) throw new Exception("Require first parameter.", rester_response::code_parameter);
        if(!$data) throw new Exception("Require second parameter.", rester_response::code_parameter);
        if(is_array($data)) $data = json_encode($data);

        self::connect_cache();
        self::$cache->set($key,$data,$timeout);
    }

    /**
     * @param string $key
     *
     * @return bool|string
     * @throws Exception
     */
    public static function get($key)
    {
        self::connect_cache();
        if($ret = self::$cache->get($key))
        {
            if(json_decode($ret,true))
            {
                $ret = json_decode($ret,true);
            }
        }
        return $ret;
    }

    /**
     * @param string $token
     *
     * @throws Exception
     */
    public static function del_token($token)
    {
        self::connect_cache();
        self::$cache->delete('token_'.$token);
    }

    /**
     * @param string $key
     *
     * @throws Exception
     */
    public static function del($key)
    {
        self::connect_cache();
        self::$cache->delete($key);
    }

    /**
     * @return string
     */
    public static function id()
    {
        return self::$session_id;
    }
}
