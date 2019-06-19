# 应用日志记录

laravel应用记录MySQL日志、请求日志、异常信息等。

# composer 安装
在项目composer.json文件中添加以下内容：
```javascript
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://gitlab.uuzu.com/songzhp/log.git"
        }
    ],

    "require": {
        "songzhp/log": "~1"
    }
}
```
然后执行 `composer install` 或 `composer update`。


或者
```bash
>_ composer config repo.emp vcs https://gitlab.uuzu.com/songzhp/log.git
>_ composer require songzhp/log
```

# 数据表创建

如果应用没有启用包自动发现，需要在 `config/app.php` 中的providers数组中添加 `Youzu\Log\LogServiceProvider::class`。确认服务提供者已经加入后，在控制台执行 `php artisan migrate` 安装所需要的数据表。

# 启用日志记录

在文件 `app/Http/Kernel.php` 中的`middleware`属性中添加 `\Youzu\Log\LogRecord::class`。该操作会同时记录request请求与请求周期内执行的SQL语句。

# 启用异常记录

在文件 `app/Exceptions/Handler.php` 中的report方法中新增以下代码：

```
$logger = app('youzu.log');
$logger->logException($exception);
```

# 说明
`app.debug` 配置项会影响日志记录的行为。在配置值为`true`时，SQL语句不会被记录到数据库中，而是记录在storage/logs下的日志文件中，而且会记录所有的SQL语句；否则，SQL会记录到数据库，并且不会记录select类型的SQL语句。