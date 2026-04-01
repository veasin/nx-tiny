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
	if(is_array($name)){
		$result = [];
		foreach($name as $key => $rule){
			$rule = is_array($rule) ? $rule : [$rule];
			$result[$key] = input($key, ...($rules ?? []), ...$rule);
		}
		return $result;
	}
	$source = '';
	$validators = [];
	foreach($rules as $rule){
		if(is_string($rule)){
			foreach(array_map('trim', explode(',', $rule)) as $part){
				if(in_array($part, ['body', 'query', 'header', 'input', 'params', 'cookie', 'file'])) $source = $part;
				else $validators[] = $part;
			}
		}
		elseif(is_array($rule)) $validators = array_merge($validators, $rule);
	}
	$value = from($name, $source ?: 'body');
	return empty($validators) ? $value : filter($value, ...$validators);
}
