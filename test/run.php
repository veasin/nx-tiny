<?php
include "../vendor/autoload.php";

use function nx\run;
use function nx\test;

// ==================== 定义测试中间件 ====================
function a($next) { echo "A("; $next(); echo ")A"; }
function b($next) { echo "B("; $next(); echo ")B"; }
function c($next) { echo "C"; }
function d($next) { echo "D("; $next(); echo ")D"; }
function e($next) { echo "E("; $next(); echo ")E"; }
function x($next) { echo "X"; }
function multi_next($next): void { echo "M("; $next(); $next(); echo ")M"; }
function deep1($next) { echo "1("; $next(); echo ")1"; }
function deep2($next) { echo "2("; $next(); echo ")2"; }
function deep3($next) { echo "3("; $next(); echo ")3"; }

// ==================== 创建临时文件中间件 ====================
$file1 = __DIR__ . '/test_mw1.php';
file_put_contents($file1, '<?php echo "f1("; $next(); echo ")f1";');

$file2 = __DIR__ . '/test_mw2.php';
file_put_contents($file2, '<?php echo "f2";');

// ==================== 测试用例 ====================

test("测试1: 标准洋葱模型",
	run_and_capture([a(...), b(...), x(...)]),
	"A(B(X)B)A");

test("测试2: 中间件未调用 \$next",
	run_and_capture([c(...), d(...)]),
	"CD()D");

test("测试3: 混合调用与未调用",
	run_and_capture([a(...), c(...), d(...)]),
	"A(CD()D)A");

test("测试4: 文件中间件",
	run_and_capture([$file1, e(...)]),
	"f1(E()E)f1");

test("测试5: 文件中间件未调用 \$next",
	run_and_capture([$file2, a(...)]),
	"f2A()A");

test("测试6: 单个中间件",
	run_and_capture([a(...)]),
	"A()A");

test("测试7: 空列表",
	run_and_capture([]),
	"");

test("测试8: 多次调用 \$next",
	run_and_capture([multi_next(...), x(...)]),
	"M(X)M");

test("测试9: 深层嵌套",
	run_and_capture([deep1(...), deep2(...), deep3(...)]),
	"1(2(3()3)2)1");

// ==================== 清理临时文件 ====================
unlink($file1);
unlink($file2);

// 辅助函数：执行中间件并捕获输出
function run_and_capture(array $fns): string {
	ob_start();
	run(...$fns);
	return ob_get_clean();
}