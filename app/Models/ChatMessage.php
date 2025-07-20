<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'group_id',
        'reply_to_id',
        'resource_id',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }


    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }


    public function replies()
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }


    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
