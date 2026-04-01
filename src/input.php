<?php
declare(strict_types=1);
namespace nx;
/**
 * * input('name', 'body','int','>0'):mixed
 * * input('name', 'body,int,>0'):mixed
 * * input(['name'=>$set], $globalSet):null|array
 * @param array|string|null $name
 * @param array|string|null $rules
 * @return mixed
 */
function input(array|string|null $name, array|string|null ...$rules): mixed{
	// 如果第一个参数是数组，则处理多个输入
	if(is_array($name)){
		$result = [];
		foreach($name as $key => $rule){
			$rule = is_array($rule) ? $rule : [$rule];
			$result[$key] = input($key, ...($rules ?? []), ...$rule);
		}
		return $result;
	}
	// 解析规则
	$source = '';
	$validators = [];
	// 处理所有传入的规则参数
	foreach($rules as $rule){
		if(is_string($rule)){
			foreach(array_map('trim', explode(',', $rule)) as $part){
				if(in_array($part, ['body', 'query', 'header', 'input', 'params', 'cookie', 'file'])) $source = $part;
				else $validators[] = $part;
			}
		}
		elseif(is_array($rule)) $validators = array_merge($validators, $rule);
	}
	static $getInput = function(){
		$result = PHP_SAPI === 'cli'
			? [
				'method' => 'cli',
				'protocol' => null,
				'uri' => implode(' ', $_SERVER['argv']),
				'params' => args(array_slice($_SERVER['argv'], 1)) ?? [],
			]
			: [
				'method' => strtolower($_SERVER['REQUEST_METHOD'] ?? 'get'),
				'protocol' => $_SERVER["SERVER_PROTOCOL"] ?? 'HTTP/1.1',
				'uri' => $_SERVER['REQUEST_URI'] ?? '/',
				'params' => null,
			];
		container("nx:input:params", $result['params']);
		container("nx:input:input", $result);
		return $result;
	};
	static $getHeaders = function(){
		if(function_exists('getallheaders')) $headers = getallheaders();
		else{
			foreach($_SERVER as $n => $v){
				if(str_starts_with($n, 'HTTP_')) $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($n, 5))))] = $v;
			}
		}
		container("nx:input:headers", $headers);
		return $headers;
	};
	static $getBody = function(){
		$content_type = input('content-type', 'header');
		if(!$content_type) return [];
		$content_type = strtolower(trim(explode(';', $content_type)[0]));
		// todo: custom content-type parser
		$raw = container("nx:input:raw") ?? file_get_contents('php://input');
		$body = match ($content_type) {
			'multipart/form-data' => $_POST,
			'application/x-www-form-urlencoded' => (function() use ($raw){
				parse_str($raw, $parsedBody);
				return $parsedBody;
			})(),
			'application/json' => json_decode($raw, true),
			//'text/html', 'text/plain' => $raw,
			default => [],
		};
		$body['RAW'] = $raw;
		container("nx:input:body", $body);
		return $body;
	};
	// 获取原始值
	$from = match ($source) {
		'query' => $_GET,
		'cookie' => $_COOKIE,
		'file' => $_FILES,
		'params' => container("nx:input:params") ?? input('params', 'input') ?? [],
		'header' => container("nx:input:headers") ?? $getHeaders(),
		'input' => container("nx:input:input") ?? $getInput(),
		'body' => container("nx:input:body") ?? $getBody(),
		default => [],
	};
	$value = $name !== null ? ($from[$name] ?? null) : $from;
	return empty($validators) ? $value : filter($value, ...$validators);
}
