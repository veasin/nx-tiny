<?php
// test/input.php
include "../vendor/autoload.php";

use function \nx\input;
use function \nx\container;
use function \nx\test;

// 模拟 GET 请求
$_GET = ['id' => '123', 'name' => 'test'];
// 模拟 POST 请求
$_POST = ['email' => 'user@example.com', 'age' => '25'];
// 模拟 Header 请求
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token123';
// 模拟 URI 参数
container('nx:input:uri', 'test-value');
$_SERVER['REQUEST_URI'] = '/user/test-value';


// 测试 query 来源
test('query 来源', input('id', 'query'), '123');

// 测试 post 来源
test('post 来源', input('email', 'post'), 'user@example.com');

// 测试 header 来源
test('header 来源', input('authorization', 'header'), 'Bearer token123');

// 测试 body 来源 (JSON)
container("nx:input:body", ['name' => 'json_user', 'age' => 30]);
test('body 来源', input('name', 'body'), 'json_user');

// 测试类型转换和验证
test('类型转换和验证', input('age', 'post', 'int', '>0'), 25);

// 测试多个输入
test('多个输入', input(['id' => 'query', 'email' => 'post'], []),
	fn($result) => is_array($result) && isset($result['id']) && isset($result['email']));