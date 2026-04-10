<?php
namespace nx\output;
/**
 * @param $response
 * @param $formats
 * @return void
 * @internal
 */
function json($response, $formats): void{
	if(null !== ($response['body'] ?? null)){
		$response['headers'] = [...($response['headers'] ?? []), 'Content-Type' => 'application/json; charset=UTF-8'];
		$options = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		if($response['pretty'] ?? false) $options |= JSON_PRETTY_PRINT;
		try{
			$response['body'] = json_encode($response['body'], $options);
		}catch(\JsonException $e){
			$response['code'] = 500;
			$response['message'] = $e->getMessage();
		}
	}
	$formats['http']($response);
}