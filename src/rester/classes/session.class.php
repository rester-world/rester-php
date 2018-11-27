<?php
/**
 * Class session
 * kevinpark@webace.co.kr
 */
class session
{
    private static $session_id;	// 세션 아이디

    /**
     * 토큰생성
     *
     * @return string token
     */
    protected static function genToken()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.';
        $token = '';
        for ($i = 0; $i < 20; $i++) {
            $token .= $characters[rand(0, strlen($characters))];
        }
        return $token;
    }

    /**
     * @param string $token
     *
     * @return bool|string
     * @throws Exception
     */
    public static function get($token)
    {
        $redis_cfg = cfg::cache();
        if(!($redis_cfg['host'] && $redis_cfg['port'])) throw new Exception("Require cache config to use auth.");

        $redis = new Redis();
        if($redis->connect($redis_cfg['host'], $redis_cfg['port'], 1.0))
        {
            if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

            if($session_id = $redis->get('token_'.$token))
            {
                self::$session_id =  $session_id;
                $redis->close();
            }
            else
            {
                $redis->close();
                throw new Exception("Can not access interface: require login token.");
            }
        }
        else
        {
            throw new Exception("Can not access redis server.");
        }
        return $session_id;
    }

    /**
     * @param string $id
     *
     * @return string
     * @throws Exception
     */
    public static function set($id)
    {
        if(!$id) throw new Exception("Require first parameter(id:string)");

        $timeout = intval(cfg::Get('session','timeout'));
        $redis_cfg = cfg::cache();
        if(!($redis_cfg['host'] && $redis_cfg['port'])) throw new Exception("Require cache config to use auth.");

        $redis = new Redis();
        $redis->connect($redis_cfg['host'], $redis_cfg['port']);
        if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

        do {
            $token = self::genToken();
        } while($redis->get('token_'.$token));

        $redis->set('token_'.$token,$id,$timeout);
        $redis->close();

        self::$session_id = $id;
        return $token;
    }

    /**
     * @return string
     */
    public static function id()
    {
        return self::$session_id;
    }
}
