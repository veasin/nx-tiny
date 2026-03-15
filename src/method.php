<?php
namespace nx;
/**
 * 获取或检查HTTP方法
 * @param string|null $method 要检查的方法
 * @return string|bool 当前方法或是否匹配 小写字符串
 */
function method(?string $method = null): string|bool{
	$current = strtolower(container('nx:method') ?? (PHP_SAPI === 'cli' ? 'cli' : $_SERVER['REQUEST_METHOD'] ?? 'get'));
	return func_num_args() === 0 ? $current : $method === $current;
}
