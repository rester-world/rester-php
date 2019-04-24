<?php

/**
 * Class rester
 * kevinpark@webace.co.kr
 *
 * 기본 핵심 모듈
 */
class rester
{
    const path_module = 'src';

    /**
     * @var rester_config
     */
    protected $cfg;

    /**
     * @var rester_verify
     */
    protected $verify;

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
     * @var string
     */
    protected $path_proc;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var bool
     */
    protected $check_auth;

    /**
     * @var bool | int
     */
    protected $cache_timeout;

    /**
     * @var string
     */
    protected $cache_key;

    /**
     * @var bool 외부접근 여부
     */
    protected $is_public_access;

    /**
     * rester constructor.
     *
     * @param string $module
     * @param string $proc
     * @param string $method
     * @param array  $request_data
     *
     * @throws Exception
     */
    public function __construct($module, $proc, $method, $request_data=[])
    {
        $this->is_public_access = false;
        $this->module = $module;
        $this->proc = $proc;
        $this->method = $method;

        $base_path = dirname(__FILE__).'/../'.self::path_module;

        // 프로시저 경로 설정
        $this->path_proc = false;
        $path = implode('/',array( $base_path, $module, $proc, $method.'.php' ));
        if(is_file($path))
        {
            $this->path_proc = $path;
        }

        // 프로시저 파일 체크
        if(!$this->path_proc)
        {
            throw new Exception("Not found procedure. Module: {$module}, Procedure: {$proc} ", rester_response::code_not_found);
        }

        // create config
        $this->cfg = new rester_config($module);

        // create verify
        $this->verify = new rester_verify($module, $proc, $method);
        $this->verify->validate($request_data);

        // check auth
        $this->check_auth = $this->cfg->is_auth($proc);

        // check cache
        $this->cache_timeout = $this->cfg->is_cache($proc);

        // set redis
        $this->redis = false;
        if($this->cache_timeout)
        {
            $redis_cfg = cfg::cache();
            if(!($redis_cfg['host'] && $redis_cfg['port']))
                throw new Exception("Require cache config to use cache.", rester_response::code_config);

            $this->redis = new Redis();
            $this->redis->connect($redis_cfg['host'], $redis_cfg['port']);
            if($redis_cfg['auth']) $this->redis->auth($redis_cfg['auth']);

            $this->cache_key = implode('_', array_merge(array($module,$proc),$this->verify->param()));
        }
    }

    public function __destruct()
    {
        if($this->redis) $this->redis->close();
    }

    /**
     * 외부접근 상태로 설정
     */
    public function set_public_access()
    {
        $this->is_public_access = true;
    }


    /**
     * run rester
     *
     * @param rester $caller
     *
     * @return array|bool|mixed
     * @throws Exception
     */
    public function run($caller=null)
    {
        // check access level [public]
        $this->check_access_level($caller);

        // check auth
        if($this->check_auth) { session::get(cfg::token()); }

        $response_data = false;

        // get cached data
        if($this->cache_timeout)
        {
            $response_data = json_decode($this->redis->get($this->cache_key),true);
        }

        // include procedure
        if(!$response_data)
        {
            if($this->path_proc)
            {
                $response_data = include $this->path_proc;
            }

            // cached body
            if($this->cache_timeout)
            {
                $this->redis->set($this->cache_key,json_encode($response_data),$this->cache_timeout);
            }
        }
        return $response_data;
    }

    /**
     * @param string $key
     *
     * @return bool|mixed
     */
    public function request_param($key)
    {
        return $this->verify->param($key);
    }

    /**
     * @return string
     */
    public function module() { return $this->module; }

    /**
     * @return string
     */
    public function proc() { return $this->proc; }

    /**
     * @param rester $caller_rester
     *
     * @return bool
     * @throws Exception
     */
    public function check_access_level($caller_rester)
    {
        $access = false;
        $ac_level = $this->cfg->access_level($this->proc);
        $caller_module = '';
        if($caller_rester!==null) $caller_module = $caller_rester->module();
        switch ($ac_level)
        {
            // 동일한 모듈
            case rester_config::access_private:
                if($this->module() == $caller_module) $access = true;
                break;

            // 외부호출 아닐때
            case rester_config::access_internal:
                if(!$this->is_public_access) $access = true;
                break;

            // 모두 통과
            case rester_config::access_public: $access = true; break;
        }
        if($access===false)
            throw new Exception("Can not access procedure. [Module] {$this->module}, [Procedure] {$this->proc}, [Access level] {$ac_level} ", rester_response::code_access_level);
        return $access;
    }

    /**
     * @return string
     */
    public function access_level() { return $this->cfg->access_level($this->proc); }
}
