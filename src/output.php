<?php
namespace nx;
use function nx\output\{http, view, json};
/**
 * 输出数据，支持多种格式和模板
 *  output($data=null, int $statusCode=200, $responseSet=[])
 *  output($data=null, string $format, $responseSet=[]):void
 *  output($data=null, string $format, $file|$responseSet)->string&header
 *  output($data=null, $responseSet=[])
 *  output($responseSet=[])
 * @param mixed|null        $data               要输出的数据
 * @param int|string|null   $formatOrStatusCode 格式名或状态码
 * @param array|string|null $responseSet        响应设置或模板文件
 * @return null
 */
function output(mixed $data = null, int|string|null $formatOrStatusCode = 200, array|string|null $responseSet = []): null{
	$statusCode = is_numeric($formatOrStatusCode) ? $formatOrStatusCode : 200;
	$format = is_string($formatOrStatusCode) ? $formatOrStatusCode : null;
	if('view' === $format){
		if(is_string($responseSet)) [$file, $responseSet] = [$responseSet, []];
		else $file = $responseSet['view'] ?? null;
	}
	container('nx:output:response', [...$responseSet, 'body' => $data, 'code' => $statusCode, 'format' => $format, 'view' => $file ?? null]);
	if(!container(null, 'nx:output:render')){
		container('nx:output:render',
			function(){
				$response = container('nx:output:response');
				//if(in_array($response['code'] ?? 0, [100, 101, 102, 204, 304])) unset($response['body']);
				$formats = [
					'json' => json(...),
					'view' => view(...),
					'http' => http(...),
					...(container('nx:output:formats') ?? []),
				];
				return $formats[$response['format'] ?? 'json']($response, $formats);
			});
		register_shutdown_function(fn() => container('nx:output:render'));
	}
	return null;
}
