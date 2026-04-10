<?php
namespace nx\cache;
/**
 * @param $next
 * @param $keyConfigName
 * @param $config
 * @param $extraConfig
 * @return mixed|null
 * @internal
 */
function apcu($next, $keyConfigName, $config, $extraConfig): mixed{
	static $hasApcu = function_exists('apcu_fetch') && function_exists('apcu_store');
	if(!$hasApcu) return $next !== null ? $next() : null;
	static $defaultTtl = 3600;
	static $prefix = '';
	if(isset($config['APCu'])){
		$apcuConfig = $config['APCu'];
		if(isset($apcuConfig[0])) $defaultTtl = $apcuConfig[0];
		if(isset($apcuConfig['_'])) $prefix = $apcuConfig['_'];
	}
	$ttl = null;
	$key = null;
	if($extraConfig){
		if(isset($extraConfig['ttl'])) $ttl = $extraConfig['ttl'];
		if(isset($extraConfig['key'])) $key = $extraConfig['key'];
	}
	if($key === null && $keyConfigName) $key = $keyConfigName;
	if($key === null) return $next !== null ? $next() : null;
	$cacheKey = $prefix . $key;
	$value = apcu_fetch($cacheKey, $success);
	if($success) return $value;
	if($next !== null){
		$value = $next();
		if($value !== null) apcu_store($cacheKey, $value, $ttl ?? $defaultTtl);
		return $value;
	}
	return null;
}