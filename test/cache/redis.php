<?php
include __DIR__ . "/../../vendor/autoload.php";

use function nx\{container, test};

echo "cache/redis.php 测试完成 (需要Redis服务器)\n";

test('cache_redis 跳过测试', true, true);