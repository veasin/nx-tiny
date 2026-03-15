<?php
// test/container.php
include "../vendor/autoload.php";

use function \nx\container;
use function \nx\test;

// 测试 1: 基本设置和获取
container('test.key', 'value');
test('基本设置获取', container('test.key'), 'value');

// 测试 2: 简单key设置和获取
container('simple_key', 'simple_value');
test('简单key设置获取', container('simple_key'), 'simple_value');

// 测试 3: 嵌套数组访问
container('nested.deep.value', 'deep_value');
test('嵌套访问', container('nested.deep.value'), 'deep_value');

// 测试 4: 删除操作
container('test.key', null);
test('删除操作', container('test.key'), null);

// 测试 5: 清空容器
container(null);
test('清空容器', container(), []);

// 测试 6: 存在性检查
container('check.key', 'exists');
test('存在性检查', container(null, 'check.key'), true);

// 测试 7: 数组批量获取
container('batch.1', 'value1');
container('batch.2', 'value2');
test('批量获取', container(['batch.1', 'batch.2']), ['value1', 'value2']);

// 测试 8: 数组批量设置
container(['batch.set1' => 'set1', 'batch.set2' => 'set2']);
test('批量设置', container(['batch.set1', 'batch.set2']), ['set1', 'set2']);

// 测试 9: 延迟构建
container('lazy', fn()=>'lazy_value');
test('延迟构建', container('lazy'), 'lazy_value');

// 测试 10: 简单key删除
container('simple_delete', 'delete_me');
container('simple_delete', null);
test('简单key删除', container('simple_delete'), null);

// 测试 11: 简单key不存在检查
test('简单key不存在检查', container(null, 'nonexistent'), false);

// 测试 12: 简单key批量获取
container('batch_simple1', 'value1');
container('batch_simple2', 'value2');
test('简单key批量获取', container(['batch_simple1', 'batch_simple2']), ['value1', 'value2']);

// 测试 13: 简单key批量设置
container(['simple_batch_set1' => 'set1', 'simple_batch_set2' => 'set2']);
test('简单key批量设置', container(['simple_batch_set1', 'simple_batch_set2']), ['set1', 'set2']);
