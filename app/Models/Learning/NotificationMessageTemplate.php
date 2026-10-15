<?php

namespace App\Models\Learning;

use Illuminate\Database\Eloquent\Model;

class NotificationMessageTemplate extends Model
{
    protected $table = 'notification_message_templates';

    protected $primaryKey = 'event_key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['event_key', 'title', 'message'];
}
