<?php

namespace Youzu\Log\Models;

use Illuminate\Database\Eloquent\Model;

class LogDb extends Model
{
    protected $fillable = [
        'user_id', 'type', 'ip', 'url', 'content',
    ];
}
