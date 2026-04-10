<?php
// token.php 测试
include __DIR__ . "/../../../../vendor/autoload.php";

use function nx\{container, middleware, test};
use function nx\middleware\prefab\token;

test('token: 无 token 返回401',
	function(){
		container('nx:mw:auth:validators', [fn($token) => $token === 'valid-token' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		middleware(token(), fn($next) => 'ok');
		return container('nx:output:response.code');
	},
	401);

test('token: 认证成功返回结果',
	function(){
		container('nx:mw:auth:validators', [fn($token) => $token === 'valid-token' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid-token';
		return middleware(token(), fn($next) => 'ok');
	},
	'ok');

test('token: 无效 token 返回403',
	function(){
		container('nx:mw:auth:validators', [fn($token) => $token === 'valid-token' ? 'user1' : null]);
		container('nx:mw:auth:user', null);
		container('nx:output:response', null);
		container('nx:from:headers', null);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid-token';
		middleware(token(), fn($next) => 'ok');
		return container('nx:output:response.code');
	},
	403);
