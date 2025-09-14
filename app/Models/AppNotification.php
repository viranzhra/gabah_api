<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id', 'dryer_id', 'process_id', 'type',
        'title', 'body', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
