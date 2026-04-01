<?php
namespace nx;
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
 * @return void
 */
function output(mixed $data = null, int|string|null $formatOrStatusCode = 200, array|string|null $responseSet = []): void{
	$statusCode = is_numeric($formatOrStatusCode) ? $formatOrStatusCode : 200;
	$format = is_string($formatOrStatusCode) ? $formatOrStatusCode : null;
	if('view' === $format){
		if(is_string($responseSet)) [$file, $responseSet] = [$responseSet, []];
		else $file = $responseSet['view'] ?? null;
	}
	container('nx:output:response', [...$responseSet, 'body' => $data, 'code' => $statusCode, 'format' => $format, 'view' => $file ?? null]);
	if(!container(null, 'nx:output:render')){
		container('nx:output:render', function(){
			$response = container('nx:output:response');
			//if(in_array($response['code'] ?? 0, [100, 101, 102, 204, 304])) unset($response['body']);
			static $formats = [
				'json' => function($response, $formats){
					if(null !== $response['body'] ?? null){
						$response['headers'] = [...($response['headers'] ?? []), 'Content-Type' => 'application/json; charset=UTF-8'];
						try{
							$response['body'] = json_encode($response['body'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						}catch(\JsonException $e){
							$response['code'] = 500;
							$response['message'] = $e->getMessage();
						}
					}
					$formats['http']($response, $formats);
				}, 'view' => function($response, $formats){
					ob_start();
					extract($response['body'] ?? []);
					include $response['view'];
					$response['body'] = ob_get_clean();
					$formats['http']($response, $formats);
				}, 'http' => function($response){
					$status = $response['code'] ?? (null !== $response['body'] ? 200 : 404);
					$message = " $status " . ($response['message'] ?? '');
					if(!headers_sent()){
					header((from('protocol', 'input') ?? "HTTP/1.1") . $message);//HTTP/1.1
					header_remove('X-Powered-By');
					$headers = $response['headers'] ?? [];
					$headers['NX'] = 'V 2005-' . date('Y');
					$is_list = array_is_list($headers);
					foreach($headers as $header => $value){
						if($is_list){
							if(is_array($value)){
								foreach($value as $v){
									header($header . ': ' . $v, false);
								}
							}
							elseif(is_string($value) || $value instanceof \Stringable){
								header($value);//['Status: 200']
							}
						}
						else header($header . ': ' . $value);
					}
					}
					$callback = container('nx:output:callback') ?? null;
					if(null !== $callback) $callback($response);
					else echo $response['body'] ?? '';
				}, ...(container('nx:output:formats') ?? []),
			];
			return $formats[$response['format'] ?? 'json']($response, $formats);
		});
		register_shutdown_function(fn()=>container('nx:output:render'));
	}
}
