<?php

namespace Youzu\Log\Models;

use Illuminate\Database\Eloquent\Model;

class LogRequest extends Model
{
    protected $fillable = [
        'user_id', 'url', 'deviceId', 'version', 'agent', 'ip', 'host', 'method', 'request', 'response',
    ];
}
