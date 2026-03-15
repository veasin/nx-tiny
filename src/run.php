<?php
namespace nx;
function run(callable|string ...$fns): void{
	$resolve = fn($fn) => is_string($fn) && is_file($fn) ? function($next) use ($fn){
		extract(['next' => $next], EXTR_SKIP);
		require $fn;
	} : $fn;
	$next = function(int $i) use (&$next, $fns, $resolve){
		if($i >= count($fns)) return;
		$called = false;
		$resolve($fns[$i])(function() use ($i, &$next, &$called){
			if($called) return;
			$called = true;
			$next($i + 1);
		});
		if(!$called) $next($i + 1);
	};
	$next(0);
}