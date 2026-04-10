<?php
namespace nx;
use function nx\cache\{apcu, redis};
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
	if(isset($fns[0]) && is_string($fns[0]) && !in_array($fns[0], ['APCu', 'Redis'])) $keyConfigName = array_shift($fns);
	if(empty($fns)) return null;
	static $handlers = ['APCu' => apcu(...), 'Redis' => redis(...)];
	static $cacheConfig = null;
	$config = null;
	if($keyConfigName){
		if($cacheConfig === null) $cacheConfig = container('cache') ?: [];
		$config = $cacheConfig[$keyConfigName] ?? null;
	}
	return middleware(...array_map(fn($fn) => match (true) {
		is_string($fn) && isset($handlers[$fn]) => fn($next, ...$params) => $handlers[$fn]($next, $keyConfigName, $config, null),
		is_array($fn) && isset($fn['fn']) && isset($handlers[$fn['fn']]) => fn($next, ...$params) => $handlers[$fn['fn']]($next, $keyConfigName, $config, $fn),
		is_callable($fn) => fn($next, ...$params) => $fn($next, $keyConfigName, $config) ?? $next(...$params),
		default => fn($next, ...$params) => $next(...$params),
	}, $fns));
}