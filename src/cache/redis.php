<?php
namespace nx\cache;

use function nx\container;

/**
 * @param $next
 * @param $keyConfigName
 * @param $config
 * @param $extraConfig
 * @return mixed|null
 * @internal
 */
function redis($next, $keyConfigName, $config, $extraConfig): mixed{
	static $hasRedis = class_exists('\Redis');
	if(!$hasRedis) return $next !== null ? $next() : null;
	static $redis = null;
	static $defaultTtl = 3600;
	static $prefix = '';
	if(isset($config['Redis'])){
		$redisConfig = $config['Redis'];
		if(isset($redisConfig[0])) $defaultTtl = $redisConfig[0];
		if(isset($redisConfig['_'])) $prefix = $redisConfig['_'];
	}
	$ttl = null;
	$key = null;
	if($extraConfig){
		if(isset($extraConfig['ttl'])) $ttl = $extraConfig['ttl'];
		if(isset($extraConfig['key'])) $key = $extraConfig['key'];
	}
	if($key === null && $keyConfigName) $key = $keyConfigName;
	if($key === null) return $next !== null ? $next() : null;
	if($redis === null){
		$redisConfig = container('config.redis') ?: ['host' => '127.0.0.1', 'port' => 6379];
		try{
			$redis = new \Redis();
			$redis->connect($redisConfig['host'], $redisConfig['port'] ?? 6379);
			if(isset($redisConfig['password'])){
				$redis->auth($redisConfig['password']);
			}
			if(isset($redisConfig['database'])){
				$redis->select($redisConfig['database']);
			}
		}catch(\Exception $e){
			$hasRedis = false;
			return $next !== null ? $next() : null;
		}
	}
	$cacheKey = $prefix . $key;
	try{
		$value = $redis->get($cacheKey);
		if($value !== false){
			return unserialize($value);
		}
	}catch(\Exception $e){
		return $next !== null ? $next() : null;
	}
	if($next !== null){
		$value = $next();
		if($value !== null){
			try{
				$redis->setex($cacheKey, $ttl ?? $defaultTtl, serialize($value));
			}catch(\Exception $e){
			}
		}
		return $value;
	}
	return null;
}