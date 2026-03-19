<?php
include "../vendor/autoload.php";
use function nx\{test, container, method};

// 测试无参调用返回当前方法
test('无参调用返回当前方法', method(), 'cli');
// 测试有参匹配检查
test('有参匹配返回true', method('cli'), true);
test('有参不匹配返回false', method('post'), false);
// 测试CLI环境
test('CLI环境返回cli', function(){
	return method();
}, 'cli');
// 测试默认值
test('无REQUEST_METHOD时默认为cli', function(){
	unset($_SERVER['REQUEST_METHOD']);
	return method();
}, 'cli');
// 测试大小写不敏感
test('大小写不敏感', function(){
	container('nx:method', 'POST');
	return method('post');
}, true);