<?php
namespace nx;
/**
 * 轻量级测试函数 - 在CLI环境下执行测试用例
 * 该函数通过register_shutdown_function延迟执行所有测试，支持直接比较和闭包断言。
 * 测试结果会以彩色输出：成功时绿色显示总结，失败时红色显示详细信息。
 * ```
 * test('数字比较', 5, 5);                    // 直接比较
 * test('函数返回值', fn() => 2+2, 4);         // value是函数
 * test('范围判断', 10, fn($v) => $v > 5);     // assign是断言函数
 * test('数组验证', ['a' => 1], function($value) {
 *     return isset($value['a']) && $value['a'] === 1;
 * });
 * ```
 * @note 该函数仅在CLI模式下有效，依赖ANSI颜色转义码
 * @note 所有测试用例在脚本结束时统一执行，支持任意数量的测试调用
 * @note 静态变量维护测试集合，多次调用自动累积
 * @param string $label  测试用例的标识名称，用于在失败时快速定位
 * @param mixed  $value  待测试的值，可以是任意类型。如果是闭包，则会执行并取其返回值
 * @param mixed  $assign 预期值或断言函数。如果是闭包，会接收$value的实际值并返回bool
 * @return void
 */
function test(string $label, mixed $value, mixed $assign): void{
	static $cases = [];
	$cases[] = [$label, $value, $assign];
	static $registered = false;
	if(!$registered){
		$registered = true;
		register_shutdown_function(function() use (&$cases){
			$total = count($cases);
			$passed = 0;
			$failed = [];
			foreach($cases as $i => [$label, $value, $assign]){
				$actual = is_callable($value) ? $value() : $value;
				$expect = is_callable($assign) ? $assign($actual) : $actual === $assign;
				if($expect === true) $passed++;
				else$failed[] = [$label, $actual, $assign];
			}
			if(empty($failed)) echo "\033[32m✔ 全部通过\033[33m: \033[32m$passed\033[33m/$total\033[0m\n";
			else{
				foreach($failed as [$label, $actual, $expect]){
					echo "\033[31m▶ {$label}\033[0m\n";
					echo "\t\033[90m预期:\033[0m\t", json_encode($expect, JSON_UNESCAPED_UNICODE), "\n";
					echo "\t\033[90m实际:\033[0m\t", json_encode($actual, JSON_UNESCAPED_UNICODE), "\n";
				}
				echo "\033[31m● 测试失败\033[33m: \033[31m" . count($failed) . "\033[0m, \033[32m$passed\033[33m/{$total}\033[0m\n";
			}
		});
	}
}