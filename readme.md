# nx-tiny: A Minimal, Declarative Functional PHP Framework

---

## 中文版

### 描述
`nx-tiny` 是一个轻量级的、函数驱动的 PHP 框架，专为现代开发实践设计。它优先考虑**配置优于代码**，支持**尾调用优化**。

它避免了复杂的类层次结构和“魔法”行为，转而采用显式的、简单的全局函数。

### 核心哲学
*   **配置 > 代码**：所有操作都由中央配置容器驱动。
*   **函数式风格**：鼓励尾调用优化和可组合的函数链。
*   **声明式路由**：路由通过注解或脚本定义并自动生成。
*   **领域模型**：模型代表业务逻辑和关系，而不仅仅是数据库 ORM 实体。
*   **缓存即逻辑**：缓存集成在业务流程中，并内置回退机制。

### 安装

```bash
composer require veasin/nx-tiny
```

### 函数参考

#### container - 容器方法

配置读取、设置与延迟构建。

```php
// 获取所有配置
$all = container();

// 检查键是否存在（支持 . 分隔）
$exists = container(null, 'database.host');  // 返回 bool

// 读取值（支持 . 分隔）
$host = container('database.host');

// 设置值
container('database.host', 'localhost');
container('app.debug', true);

// 批量设置
container([
    'database.host' => '127.0.0.1',
    'database.port' => 3306,
]);

// 延迟构建
container('version', fn() => file_get_contents('version.txt'));
$version = container('version');  // 访问时自动执行
```

#### args - 命令行参数解析

```php
// 字符串输入
$args = args('-v --file=test.php input.txt');
// 结果: ['v' => true, 'file' => 'test.php', 0 => 'input.txt']

// 数组输入
$args = args(['-abc', '--verbose', '--name=John', 'data.txt']);
// 结果: ['a' => true, 'b' => true, 'c' => true, 'verbose' => true, 'name' => 'John', 0 => 'data.txt']

// 带引号的值
$args = args('--message="Hello World"');
// 结果: ['message' => 'Hello World']
```

#### method - HTTP方法获取/检查

```php
// 获取当前请求方法
$method = method();  // 返回: 'get', 'post', 'cli' 等

// 检查是否匹配指定方法
if (method('POST')) {
    // 处理 POST 请求
}
```

#### input - 输入数据获取

```php
// 获取查询参数
$id = input('id', 'int', '>0');

// 获取 Body 参数（JSON）
$name = input('name', 'body');

// 获取 URL 参数
$slug = input('slug', 'uri');

// 获取请求头
$token = input('authorization', 'header');

// 获取 CLI 参数
$cmd = input('cmd', 'params');

// 获取并验证（多个规则）
$age = input('age', 'int', '>=18', '<=100');

// 批量获取
$data = input(['id' => 'int,>0', 'name' => 'str', 'email' => 'email']);
```

#### filter - 数据验证与转换

```php
// 类型转换
filter('123', 'int');        // 返回 123 (int)
filter('true', 'bool');      // 返回 true
filter('{"a":1}', 'json');   // 返回 ['a' => 1]

// 验证规则
filter('hello@example.com', 'email');  // 返回邮箱字符串
filter('150', 'int', '>100', '<200');  // 返回 150
filter('on', 'bool');                  // 返回 true

// 自定义验证
filter('abc', fn($v) => strlen($v) > 2);  // 返回 'abc'
filter(10, 'int', '>5');                  // 返回 10
filter(3, 'int', '>5');                   // 返回 null (验证失败)
```

#### output - 输出数据

```php
// JSON 输出
output(['status' => 'ok', 'data' => [1, 2, 3]]);

// 设置状态码
output(['error' => 'not found'], 404);

// 指定格式输出
output($data, 'json');
output(['view' => 'template.php'], 'view');

// 带响应头
output(['token' => $token], 200, ['Authorization' => 'Bearer xxx']);
```

#### route - 路由匹配

```php
// 基础路由
route('GET:/users', function() {
    output(['users' => []]);
});

// 带参数
route('GET:/user/:id', function() {
    $id = input('id', 'uri');
    output(['id' => $id]);
});

// POST 路由
route('POST:/api/user', function() {
    $name = input('name', 'body');
    output(['created' => $name]);
});

// 多路由同一处理
route(['GET:/api/list', 'POST:/api/create'], $handler);

// CLI 路由
route('cli:verbose', function() { /* ... */ });
route('cli:file=*', function() { /* ... */ });
```

#### middleware - 中间件执行（洋葱模型）

```php
// 基础用法
$result = middleware(
    function($next) {
        echo "Before\n";
        $result = $next();
        echo "After\n";
        return $result;
    },
    function($next, $value) {
        return $value + 1;
    },
    5  // 初始值
);

// 日志中间件
$log = fn($next, ...$args) => tap($next(...$args), fn($r) => log($r));

// 链式执行
$result = middleware($auth, $validation, $handler, $response);
```

#### run - 执行中间件链

```php
// 简单链式调用
$result = run(
    fn($v) => $v + 1,
    fn($v) => $v * 2,
    5  // 初始值
);
// 结果: 12 ( (5+1)*2 )

// 条件执行
$result = run(
    $condition ? $middleware1 : null,
    $handler
);
```

#### cache - 多级缓存

```php
// APCu 缓存
$result = cache('APCu', function() {
    return db('SELECT * FROM users');
});

// 带 TTL
$result = cache(['fn' => 'Redis', 'ttl' => 3600], function() {
    return expensiveOperation();
});

// 组合缓存
$result = cache('APCu', 'Redis', function() {
    return $data;
});
```

#### db - 数据库操作

```php
// 查询单行
$user = db('SELECT * FROM users WHERE id = ?', [1], 'row');

// 查询列表
$users = db('SELECT * FROM users', [], 'list');

// 查询单个值
$count = db('SELECT COUNT(*) FROM users', [], 'value');

// 插入并获取ID
$id = db('INSERT INTO users (name) VALUES (?)', ['John'], 'id');

// 更新并获取影响行数
$count = db('UPDATE users SET name = ? WHERE id = ?', ['Jane', 1], 'count');

// 批量插入
db('INSERT INTO users (name) VALUES (?), (?)', [['John'], ['Jane']], 'ok');
```

**事务支持**（需要4个参数）：

```php
// 开启事务
db('BEGIN');

// 提交事务
db('COMMIT');

// 回滚事务
db('ROLLBACK');

// 保存点（支持嵌套）
db('SAVEPOINT sp1');

// 回滚到保存点
db('ROLLBACK TO SAVEPOINT sp1');
```

配合 [nx-sql](https://github.com/veasin/nx-sql) 使用：

```php
use nx\helpers\sql;

// 插入数据并获取ID
$id = db(sql::table('users')->insert(['name' => 'John', 'email' => 'john@test.com']), 'id');

// 查询单行
$user = db(sql::table('users')->where(['id' => 1])->select(), 'row');

// 条件查询
$activeUsers = db(sql::table('users')->where(['status' => 1])->select(), 'list');

// 更新数据
$affected = db(sql::table('users')->where(['id' => 1])->update(['name' => 'Jane']), 'count');

// 删除数据
$affected = db(sql::table('users')->where(['id' => 1])->delete(), 'count');
```

#### test - 轻量级测试

```php
// 直接比较
test('数字比较', 5, 5);

// 函数返回值
test('函数返回值', fn() => 2+2, 4);

// 断言函数
test('范围判断', 10, fn($v) => $v > 5);

// 数组验证
test('数组验证', ['a' => 1], function($value) {
    return isset($value['a']) && $value['a'] === 1;
});
```

#### name - 命名配置管理

```php
// 基础用法
$key = name('user.id');  // 返回 'user.id'

// 命名空间
container('name', ['cache' => ['user' => 'cache:user:{uid}']]);
$key = name('user', ['uid' => 123], 'cache');  // 返回 'cache:user:123'
```
