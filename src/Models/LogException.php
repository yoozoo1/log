<?php

namespace Youzu\Log\Models;

use Illuminate\Database\Eloquent\Model;

class LogException extends Model
{
    protected $fillable = [
        'level', 'line', 'content',
    ];
}
