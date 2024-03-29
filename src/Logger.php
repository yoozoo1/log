<?php

namespace Youzu\Log;

use Closure;
use DateTime;
use DB;
use Exception;
use Log;
use Youzu\Log\Models\LogDb;
use Youzu\Log\Models\LogException;
use Youzu\Log\Models\LogRequest;

class Logger
{
    private $querys     = [];
    private $exceptions = [];
    private $userResolve;
    /**
     * 记录请求日志
     *
     * @method  action
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:26:39+0800
     * @param   Rquest    $request   请求
     * @param   Response  $response  响应
     * @return  Youzu\Log\Models\LogRequest
     */
    public function action($request, $response)
    {
        $log = [
            'user_id'  => $this->getUserId(),
            'url'      => $request->path(),
            'deviceId' => $request->header('DeviceId', ''),
            'version'  => $request->header('Version', ''),
            'agent'    => $request->server->get('HTTP_USER_AGENT', 'robots'),
            'ip'       => $request->ip(),
            'host'     => $request->server->get('SERVER_ADDR'),
            'method'   => $request->method(),
            'request'  => $request->isMethod('get')
            ? $request->getQueryString()
            : http_build_query($this->removePasswordFormPost($request->post())),
            'response' => $response->getStatusCode(),
        ];

        return LogRequest::create($log);
    }

    /**
     * 不记录POST请求中的密码项
     *
     * @method  removePasswordFormPost
     * @author  雷行  songzhp@yoozoo.com  2019-11-18T15:57:49+0800
     * @param   array                  $post
     * @return  array
     */
    private function removePasswordFormPost($post)
    {
        foreach ($post as $key => $value) {
            if (preg_match('/password/', $key)) {
                unset($post[$key]);
            }
        }
        return $post;
    }

    /**
     * 监听数据库执行语句
     *
     * @method  listenDB
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:27:23+0800
     * @return  void
     */
    public function listenDB()
    {
        $filterTables = config('log.filter.tables');
        DB::listen(function ($query) use ($filterTables) {

            $types = ['insert', 'update', 'delete'];
            $type  = substr($query->sql, 0, 6);
            if (in_array($type, $types) || config('app.debug')) {
                if (!empty($filterTables) && $this->filterWithTable($query->sql, $filterTables)) {
                    return;
                }
                $this->querys[$type][] = $query;
            }
        });
    }

    /**
     * 按表名进行过滤
     *
     * @method  filterWithTable
     * @author  雷行  songzhp@yoozoo.com  2019-10-23T09:45:39+0800
     * @param   string           $sql           当前查询语句
     * @param   array            $filterTables  要过滤的表名
     * @return  boolean
     */
    public function filterWithTable($sql, $filterTables)
    {

        $result = preg_match('/\`([^\`]*)\`/', $sql, $matched);
        if ($result) {
            $table = $matched[1];
            return in_array($table, $filterTables);
        }
        return false;
    }

    /**
     * 记录数据库日志
     *
     * @method  db
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:27:50+0800
     * @param   Rquest    $request   请求
     * @param   Response  $response  响应
     * @return  void
     */
    public function db($request, $response)
    {
        $url = $request->path();
        $ip  = $request->ip();

        foreach ($this->querys as $type => $querys) {
            foreach ($querys as $query) {
                $sql      = $this->string($query);
                $duration = $this->formatDuration($query->time / 1000);

                if (config('app.debug')) {
                    $sql = '[' . $duration . '] ' . $sql;
                    Log::debug($sql);
                    continue;
                }

                $log = [
                    'user_id'  => $this->getUserId(),
                    'type'     => $type,
                    'ip'       => $ip,
                    'url'      => $url,
                    'content'  => $sql,
                    'duration' => $duration,
                ];
                LogDb::create($log);
            }
        }
    }

    /**
     * 记录命令行运行的SQL
     *
     * @method  consoleDB
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:28:22+0800
     * @return  void
     */
    public function consoleDB()
    {
        foreach ($this->querys as $type => $querys) {
            foreach ($querys as $query) {
                $sql = $this->string($query);

                $duration = $this->formatDuration($query->time / 1000);

                $sql = '[' . $duration . '] ' . $sql;
                if (config('app.debug')) {
                    Log::debug($sql);
                    continue;
                }
            }
        }
    }

    /**
     * Query转string
     *
     * @method  string
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:28:40+0800
     * @param   Query  $query
     * @return  string
     */
    private function string($query)
    {

        foreach ($query->bindings as $i => $binding) {
            if ($binding instanceof DateTime) {
                $query->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
            } else {
                if (is_string($binding)) {
                    $query->bindings[$i] = "'$binding'";
                }
            }
        }

        $sql = str_replace(array('%', '?'), array('%%', '%s'), $query->sql);
        $sql = vsprintf($sql, $query->bindings);
        return $sql;
    }

    /**
     * 缓存异常信息
     *
     * @method  logException
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:29:10+0800
     * @param   Exception     $e
     * @return  void
     */
    public function logException(Exception $e)
    {
        $time               = date('Y-m-d H:i:s');
        $this->exceptions[] = [
            'level'      => $e->getCode(),
            'line'       => $e->getFile() . ' - ' . $e->getLine(),
            'content'    => $e->getMessage(),
            'created_at' => $time,
            'updated_at' => $time,
        ];
    }

    /**
     * 记录异常信息
     *
     * @method  exception
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:29:48+0800
     * @return  void
     */
    public function exception()
    {
        if (!empty($this->exceptions)) {
            LogException::insert($this->exceptions);
        }
    }

    /**
     * 获取登陆用户ID
     *
     * @method  getUserId
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T11:30:00+0800
     * @return  integer
     */
    public function getUserId()
    {
        if (isset($this->userResolve)) {
            $user = call_user_func($this->userResolve);
            return $user->id;
        }
        return 0;
    }

    /**
     * 设置登陆用户解析
     *
     * @method  setUserResolve
     * @author  雷行  songzhp@yoozoo.com  2019-06-19T12:06:22+0800
     * @param   Closure         $callback
     */
    public function setUserResolve(Closure $callback)
    {
        $this->userResolve = $callback;
    }

    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }
        return round($seconds, 2) . 's';
    }

    /**
     * 手动清除已记录的SQL
     *
     * @method  cleanQuery
     * @author  雷行  songzhp@yoozoo.com  2019-12-06T15:14:56+0800
     * @return  void
     */
    public function cleanQuery()
    {
        $this->querys = [];
    }
}
