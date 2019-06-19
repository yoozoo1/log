<?php

namespace Youzu\Log;

use Closure;

/**
 * 系统日志
 *
 *  在处理完请求后，进行日志记录工作
 */
class LogRecord
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $logger = app('youzu.log');

        // 1. 先记录数据库日志，避免后续日志的SQL操作被记入
        $logger->db($request, $response);

        // 2. 记录请求日志
        $logger->action($request, $response);

        // 3. 记录异常日志
        $logger->exception();
    }
}
