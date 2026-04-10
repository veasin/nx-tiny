<?php
// apikey.php 测试
include __DIR__ . "/../../../../vendor/autoload.php";

use function nx\{container, middleware, test};
use function nx\middleware\prefab\apikey;

test('apikey: 无 apiKey 返回401',
	function(){
		container('nx:mw:auth:validators', [fn($apiKey) => $apiKey === 'test-api-key' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		middleware(apikey(), fn($next) => 'ok');
		return container('nx:output:response.code');
	},
	401);

test('apikey: 从 header 认证成功',
	function(){
		container('nx:mw:auth:validators', [fn($apiKey) => $apiKey === 'test-api-key' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_X_API_KEY'] = 'test-api-key';
		return middleware(apikey('nx:mw:auth', 'x-api-key'), fn($next) => 'ok');
	},
	'ok');

test('apikey: 从 query 认证成功',
	function(){
		container('nx:mw:auth:validators', [fn($apiKey) => $apiKey === 'test-api-key' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET['api_key'] = 'test-api-key';
		return middleware(apikey('nx:mw:auth', 'x-api-key'), fn($next) => 'ok');
	},
	'ok');

test('apikey: 无效 apiKey 返回403',
	function(){
		container('nx:mw:auth:validators', [fn($apiKey) => $apiKey === 'test-api-key' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_X_API_KEY'] = 'invalid-key';
		middleware(apikey('nx:mw:auth', 'x-api-key'), fn($next) => 'ok');
		return container('nx:output:response.code');
	},
	403);
