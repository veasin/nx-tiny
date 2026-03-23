<?php
namespace nx;
/**
 * 多级缓存函数
 * 调用方式:
 * 1. cache(string $keyConfigName, ...$fns) - 使用配置名
 * 2. cache(...$fns) - 直接使用缓存函数
 * 缓存方法格式:
 * - 闭包: fn($next, $keyConfigName = null, $config = null)
 * - 字符串: 'APCu', 'Redis' 等预定义缓存方法
 * - 数组: ['fn'=>'Redis', 'ttl'=>3600, 'key'=>name('uid')]
 * @param mixed ...$fns
 * @return mixed
 */
function cache(...$fns): mixed{
	$keyConfigName = null;
	if(isset($fns[0]) && is_string($fns[0]) && !in_array($fns[0], ['APCu', 'Redis'])) $keyConfigName = array_shift($fns);// 第一个参数如果是字符串，则为配置名
	if(empty($fns)) return null;
	static $APCu = function($next, $keyConfigName, $config, $extraConfig){
		static $hasApcu = function_exists('apcu_fetch') && function_exists('apcu_store');
		if(!$hasApcu) return $next !== null ? $next() : null;
		static $defaultTtl = 3600;
		static $prefix = '';
		// 从配置获取默认值
		if(isset($config['APCu'])){
			$apcuConfig = $config['APCu'];
			if(isset($apcuConfig[0])) $defaultTtl = $apcuConfig[0];
			if(isset($apcuConfig['_'])) $prefix = $apcuConfig['_'];
		}
		// 合并额外配置
		if($extraConfig){
			if(isset($extraConfig['ttl'])) $ttl = $extraConfig['ttl'];
			if(isset($extraConfig['key'])) $key = $extraConfig['key'];
		}
		if($key === null && $keyConfigName) $key = $keyConfigName;// 如果没有指定key，使用配置名
		if($key === null) return $next !== null ? $next() : null;// 如果key为null，无法操作缓存
		$cacheKey = $prefix . $key;
		$value = apcu_fetch($cacheKey, $success);
		if($success) return $value;
		// 未命中，调用下一个
		if($next !== null){
			$value = $next();
			if($value !== null) apcu_store($cacheKey, $value, $ttl ?? $defaultTtl);
			return $value;
		}
		return null;
	};
	static $Redis = function($next, $keyConfigName, $config, $extraConfig){
		static $hasRedis = class_exists('\Redis');
		if(!$hasRedis) return $next !== null ? $next() : null;
		static $redis = null;
		static $defaultTtl = 3600;
		static $prefix = '';
		// 从配置获取默认值
		if(isset($config['Redis'])){
			$redisConfig = $config['Redis'];
			if(isset($redisConfig[0])) $defaultTtl = $redisConfig[0];
			if(isset($redisConfig['_'])) $prefix = $redisConfig['_'];
		}
		// 合并额外配置
		if($extraConfig){
			if(isset($extraConfig['ttl'])) $ttl = $extraConfig['ttl'];
			if(isset($extraConfig['key'])) $key = $extraConfig['key'];
		}
		// 如果没有指定key，使用配置名
		if($key === null && $keyConfigName){
			$key = $keyConfigName;
		}
		// 如果key为null，无法操作缓存
		if($key === null){
			return $next !== null ? $next() : null;
		}
		// 初始化Redis连接
		if($redis === null){
			// 从容器获取Redis配置
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
				// 连接失败，标记为不可用
				$hasRedis = false;
				return $next !== null ? $next() : null;
			}
		}
		$cacheKey = $prefix . $key;
		// 尝试读取
		try{
			$value = $redis->get($cacheKey);
			if($value !== false){
				return unserialize($value);
			}
		}catch(\Exception $e){
			// 读取失败，继续下一个
			return $next !== null ? $next() : null;
		}
		// 未命中，调用下一个
		if($next !== null){
			$value = $next();
			if($value !== null){
				try{
					$redis->setex($cacheKey, $ttl ?? $defaultTtl, serialize($value));
				}catch(\Exception $e){
					// 写入失败，忽略
				}
			}
			return $value;
		}
		return null;
	};
	static $handlers = ['APCu' => $APCu, 'Redis' => $Redis];
	static $cacheConfig = null;
	$config = null;
	if($keyConfigName){
		if($cacheConfig === null) $cacheConfig = container('cache') ?: [];// 从容器获取配置，如果没有则使用默认空数组
		$config = $cacheConfig[$keyConfigName] ?? null;
	}
	return middleware(...array_map(fn($fn) => match (true) {
		is_string($fn) && isset($handlers[$fn]) => fn($next, ...$params) => $handlers[$fn]($next, $keyConfigName, $config, null),
		is_array($fn) && isset($fn['fn']) && isset($handlers[$fn['fn']]) => fn($next, ...$params) => $handlers[$fn['fn']]($next, $keyConfigName, $config, $fn),
		is_callable($fn) => fn($next, ...$params) => $fn($next, $keyConfigName, $config) ?? $next(...$params),
		default => fn($next, ...$params) => $next(...$params),
	}, $fns));
}