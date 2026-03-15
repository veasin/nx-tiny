<?php
declare(strict_types=1);
namespace nx;
/**
 * 容器方法，支持配置读取、设置与延迟构建
 * 基本操作示例：
 * ```
 * // 获取所有配置
 * $all = container();
 * // 清空配置
 * container(null);
 * // 检查键是否存在（支持 . 分隔）
 * $exists = container(null, 'database.host');  // 返回 bool
 * // 读取值（支持 . 分隔）
 * $host = container('database.host');  // 不存在返回 null
 * // 设置值（支持 . 分隔）
 * container('database.host', 'localhost');
 * container('app.debug', true);
 * // 删除键（设置 null）
 * container('database.host', null);
 * // 批量读取（list 数组）
 * $values = container(['database.host', 'app.debug']);  // 返回对应的值数组
 * // 批量设置（map 数组）
 * container([
 *     'database.host' => '127.0.0.1',
 *     'database.port' => 3306,
 *     'app.debug' => false,
 *     'app.cache' => null  // 删除键
 * ]);
 * // 延迟构建（存储可调用对象，访问时自动执行）
 * container('version', fn() => file_get_contents('version.txt'));
 * $version = container('version');  // 自动执行并返回结果
 * ```
 * 返回值说明：
 * - container()                           : array  全部配置
 * - container(null)                       : array  空数组
 * - container(null, string)                : bool   键是否存在
 * - container(string)                      : mixed  键对应的值（不存在返回 null）
 * - container(string, mixed)               : void   设置值（null 表示删除）
 * - container(array)                       : array|void list数组返回批量读取结果，map数组进行批量设置
 * @param array|string|null $key   键名，支持 . 分隔访问嵌套数组，或数组形式批量操作
 * @param mixed|null        $value 值，若为 null 则删除键
 * @return mixed 读取时返回值，设置时返回 void
 */
function container(array|string|null $key = null, mixed $value = null): mixed{
	static $container = [];
	static $get = function(string $key, $check = null) use (&$container): mixed{
		$current = &$container;
		foreach(explode('.', $key) as $k){
			if(!is_array($current) || !array_key_exists($k, $current)) return $check;
			$current = &$current[$k];
		}
		return false === $check ? true : (is_callable($current) ? $current() : $current);
	};
	static $set = function(string $key, mixed $value) use (&$container): void{
		$keys = explode('.', $key);
		$current = &$container;
		$lastKey = array_pop($keys);
		foreach($keys as $k){
			if(!isset($current[$k]) || !is_array($current[$k])) $current[$k] = [];
			$current = &$current[$k];
		}
		if($value === null) unset($current[$lastKey]);
		else$current[$lastKey] = $value;
	};
	return match (func_num_args()) {
		0 => $container,
		1 => match (true) {
			$key === null => $container = [],
			is_array($key) => array_is_list($key) ? array_map($get, $key) : (fn() => array_walk($key, fn($v, $k) => $set($k, $v)))(),
			default => $get($key)
		},
		2 => match (true) {
			$key === null && is_string($value) => $get($value, false),
			is_string($key) => $set($key, $value),
			default => null
		},
	};
}