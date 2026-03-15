<?php
namespace nx;
/**
 * 路由注册函数
 * @param string|array $match  匹配规则或路由映射数组
 * @param callable     ...$fns 路由处理函数列表
 * @return void
 */
function route(string|array $match, callable ...$fns): void{
	if(is_array($match)){
		foreach($match as $m => $fn) route($m, is_array($fn) ? $fn[0] : $fn);
		return;
	}
	if(empty($fns)) return;
	[$method, $uri] = explode(':', $match, 2) + ['', ''];
	$matched = $method === '*' || method($method);
	if(!$matched) return;
	if($method === 'cli'){
		$args = args(container('nx:route:argv') ?? ($_SERVER['argv'] ? array_slice($_SERVER['argv'], 1) : []));
		$cliArgs = args(substr($match, 4));
		if(array_any($cliArgs, fn($value, $key) => !isset($args[$key]) || ($args[$key] !== $value && $args[$key] !== true))) return;
	}
	else{
		$uri = trim($uri);
		if(!empty($uri)){
			$uriPattern = preg_replace_callback('/[:{]([a-zA-Z0-9_]+)[}]/', fn($m) => '(?P<' . $m[1] . '>[^/]+)', $uri);
			if(preg_match('#^' . $uriPattern . '$#', container('nx:route:uri') ?? ($_SERVER['REQUEST_URI'] ?? '/'), $matches)){
				container('nx:input:uri', array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY));
			}
			else return;
		}
	}
	run(...$fns);
}
