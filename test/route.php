<?php
include __DIR__ . "/../vendor/autoload.php";
use function nx\{test, container, input, route};

test('参数路由匹配', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/user/123';
	$id = null;
	route('get:/user/{id}', function() use (&$id) {
		$id = input('id', 'uri');
	});
	return $id;
}, '123');

test('方法筛选与参数提取', function() {
	container('nx:method', 'POST');
	$_SERVER['REQUEST_URI'] = '/post/abc';
	$result = '';
	route([
		'get:/' => fn() => $result = 'get',
		'post:/post/{id}' => [function() use (&$result) {
			$result = 'post:' . input('id', 'uri');
		}]
	]);
	return $result;
}, 'post:abc');

test('CLI参数匹配', function() {
	container('nx:method', 'CLI');
	$_SERVER['argv'] = ['script.php', '--id=123', '--name=test'];
	$called = false;
	route('cli:--id=123 --name=test', function() use (&$called) {
		$called = true;
	});
	return $called;
}, true);

test('通配符方法匹配', function() {
	container('nx:method', 'PUT');
	$_SERVER['REQUEST_URI'] = '/any/route';
	$called = false;
	route('*:/any/{path}', function() use (&$called) {
		$called = true;
	});
	return $called;
}, true);

test('多参数注入容器', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/user/456/post/789';
	route('get:/user/{uid}/post/{pid}', function() {});
	return container('nx:input:uri');
}, ['uid' => '456', 'pid' => '789']);

test('不匹配路由跳过', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/wrong/path';
	$called = false;
	route('get:/correct/path', function() use (&$called) {
		$called = true;
	});
	return $called === false;
}, true);

test('CLI无参数匹配', function() {
	container('nx:method', 'CLI');
	$_SERVER['argv'] = ['script.php', 'command'];
	$called = false;
	route('cli:command', function() use (&$called) {
		$called = true;
	});
	return $called;
}, true);

test('多路由顺序 - /a/123 匹配参数和通配符', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/a/123';
	$log = [];
	route([
		'get:/a/:id' => function() use (&$log) { $log[] = 'fn1'; },
		'get:/a/*' => function() use (&$log) { $log[] = 'fn2'; },
	]);
	return $log;
}, ['fn1', 'fn2']);

test('多路由顺序 - /a/123 匹配通配符和参数', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/a/123';
	$log = [];
	route([
		'get:/a/b/c' => function() use (&$log) { $log[] = 'fn1'; },
		'get:/a/*' => function() use (&$log) { $log[] = 'fn2'; },
		'get:/a/:id' => function() use (&$log) { $log[] = 'fn3'; },
	]);
	return $log;
}, ['fn2', 'fn3']);

test('多路由顺序 - /a/b/c 仅通配符匹配', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/a/b/c';
	$log = [];
	route([
		'get:/a/b' => function() use (&$log) { $log[] = 'fn1'; },
		'get:/a/*' => function() use (&$log) { $log[] = 'fn2'; },
		'get:/a/:id' => function() use (&$log) { $log[] = 'fn3'; },
	]);
	return $log;
}, ['fn2']);

test('多路由顺序 - /a/b/c 精确匹配和通配符', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/a/b/c';
	$log = [];
	route([
		'get:/a/b/c' => function() use (&$log) { $log[] = 'fn1'; },
		'get:/a/*' => function() use (&$log) { $log[] = 'fn2'; },
		'get:/a/:id' => function() use (&$log) { $log[] = 'fn3'; },
	]);
	return $log;
}, ['fn1', 'fn2']);
