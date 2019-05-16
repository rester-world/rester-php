<?php
/**
 * Class resterPHP
 */
class resterPHP extends rester
{
    public function run($caller=null)
    {
        // check access level [public]
        $this->check_access_level($caller);

        // check auth
        if(cfg::token())
        {
            session::get_token(cfg::token());
        }
        if($this->check_auth && !session::id())
        {
            throw new Exception("Login required!", rester_response::code_require_login);
        }

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
}
