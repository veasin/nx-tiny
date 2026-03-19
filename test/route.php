<?php
include "../vendor/autoload.php";
use function nx\{test, container, input, route};

// 基础路由匹配测试
test('基础GET路由匹配', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/user/123';
	$called = false;
	route('get:/user/{id}', function() use (&$called) {
		$called = input('id', 'uri');
	});
	return $called;
}, '123');

// 数组批量注册测试
test('数组批量路由注册', function() {
	container('nx:method', 'POST');
	$_SERVER['REQUEST_URI'] = '/post/abc';
	$result = '';
	route([
		'get:/' => fn() => $result = 'get',
		'post:/post/{id}' => [function()use(&$result){
		 $result = 'post:' . input('id', 'uri');
	}]]);
	return $result;
}, 'post:abc');

// CLI参数匹配测试
test('CLI路由参数匹配', function() {
	container('nx:method', 'CLI');
	$_SERVER['argv'] = ['script.php', '--id=123', '--name=test'];
	$called = false;
	route('cli:--id=123 --name=test', function() use (&$called) {
		$called = true;
	});
	return $called;
}, true);

// 通配符方法测试
test('通配符*方法匹配', function() {
	container('nx:method', 'PUT');
	$_SERVER['REQUEST_URI'] = '/any/route';
	$called = false;
	route('*:/any/{path}', function() use (&$called) {
		$called = true;
	});
	return $called;
}, true);

// URI参数注入测试
test('URI参数注入到容器', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/user/456/post/789';
	route('get:/user/{uid}/post/{pid}', function() {});
	return container('nx:input:uri');
}, ['uid'=>'456', 'pid'=>'789']);

// 不匹配路由测试
test('路由不匹配应跳过', function() {
	container('nx:method', 'GET');
	$_SERVER['REQUEST_URI'] = '/wrong/path';
	$called = false;
	route('get:/correct/path', function() use (&$called) {
		$called = true;
	});
	return $called === false;
}, true);

// CLI无参数匹配测试
test('CLI无参数路由', function() {
	container('nx:method', 'CLI');
	$_SERVER['argv'] = ['script.php', 'command'];
	$called = false;
	route('cli:command', function() use (&$called) {
		$called = true;
	});
	return $called;
}, true);