<?php
namespace Session {
    /**
* Redis Session
*/
class Redis
{
    private $name,
            $secret,
            $fingerprint,
            $client;

    public function __construct($client, $name='sid', $secret=false, $maxAge = 240, $fingerprint=false)
    {
        $this->client = $client;
        $this->name = $name;
        $this->secret = ($secret)? $secret : uid(50);
        $this->fingerprint = ($fingerprint)? $fingerprint : $this->defaultFingerprint();
        session_set_save_handler(
            array($this,'open'),
            array($this,'close'),
            array($this,'read'),
            array($this,'write'),
            array($this,'destroy'),
            array($this, 'gc')
        );
        session_cache_expire(240);
        session_name($this->name);
        if(!isset($_COOKIE[$this->name]) || !preg_match('/.{24}\..{43}/', $_COOKIE[$this->name])) {
            session_id($this->generate_session_id());
        }
    }

    public function start()
    {
        
        session_start();
        
    }

    public function open($path, $name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        if($data = $this->client->get('sess:'.$id)) {
            $results = json_decode($data, true);
            return $this->serialize($results);    
        }

        return '';
    }

    public function write($id, $str)
    {
        $data = $this->unserialize($str);
        $data['lastAccess'] = time()*1000;
        $data['cookie'] = array('originalMaxAge'=>session_cache_expire()*60000, 'expires'=>gmstrftime('%Y-%m-%dT%H:%M:%SZ',session_cache_expire()*60+time()), 'httpOnly'=>true, "path"=>'/');
        $reply = $this->client->set('sess:'.$id, json_encode($data));
        $this->client->expire('sess'.$id, session_cache_expire()*60);
        return $reply;
    }

    public function destroy($id)
    {
        return $this->client->del('sess:'.$id);
    }

    // When we set the key in redis we also set the TTL
    // So we don't need to collect garbage.
    public function gc()
    {
        return true;   
    }

    protected function serialize($hash=array())
    {
        $str = '';
        $hash = (array) $hash;
        foreach($hash as $key=>$value) {
            $str .= $key.'|'.serialize($value);
        }
        return $str;
    }

    protected function unserialize($str)
    {
        // The regex to get the keys
        $regex = '/([^;\}\|]+)\|/';

        // Get all the keys
        $matches = array();
        preg_match_all($regex, $str, $matches);
        $keys = $matches[1];
        
        // Split out all the serialized values
        $serialized_values = preg_split($regex, $str);

        // Unserialize all the values
        $values = array();
        foreach($serialized_values as $val) {
            if(!empty($val)) $values[] = unserialize($val);
        }

        return array_combine($keys, $values);
    }

    protected function generate_session_id()
    {
        $base = $this->uid(24);
        return $base.'.'.$this->get_hash($base);
    }

    protected function uid($length)
    {
      $buf = array();
      $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      for($i=0;$i<$length; ++$i) {
          $buf[] = $chars{mt_rand(0, strlen($chars)-1)};
      }
      return join('',$buf);
    }

    protected function get_hash($base)
    {
        return preg_replace('/=*$/','',base64_encode(hash_hmac('sha256', $base.$this->fingerprint, $this->secret, true)));
    }

    protected function defaultFingerprint()
    {
        return preg_replace('/;?\schromeframe\/[\d\.]+/', '', $_SERVER['HTTP_USER_AGENT']);

    }
}    
}
