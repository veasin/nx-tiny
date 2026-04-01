<?php
require __DIR__ . '/../vendor/autoload.php';

use function nx\{container, input, test};

container("nx:input:input", [
	'method' => 'get',
	'uri' => '/users/123?name=test',
	'params' => null,
]);
container('nx:input:params', ['id' => '123', 'name' => 'test']);
$_GET = ['name' => 'test'];
test('input source - 完整数组', input(null, 'input'), fn($v) => is_array($v) && isset($v['method']) && isset($v['uri']));
test('input source - method', input('method', 'input'), 'get');
test('input source - uri', input('uri', 'input'), '/users/123?name=test');
test('input source - params (web)', input('params', 'input'), null);
test('params source - 容器有值', input('id', 'params'), '123');
test('params source - 容器无值', input('name', 'params'), 'test');
container("nx:input:input", [
	'method' => 'cli',
	'uri' => 'cli.php --name=test --id=123',
	'params' => ['name' => 'test', 'id' => '123'],
]);
test('input source - cli mode', input('method', 'input'), 'cli');
test('input source - cli uri', input('uri', 'input'), 'cli.php --name=test --id=123');
test('input source - cli params', input('params', 'input'), fn($v) => is_array($v) && isset($v['name']));
test('query source', input('name', 'query'), 'test');
test('name=null 返回数组', input(null, 'query'), ['name' => 'test']);
