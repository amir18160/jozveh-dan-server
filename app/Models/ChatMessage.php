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

    /**
     * The author of the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The group this message belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * If this is a reply, the parent message.
     */
    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    /**
     * Replies to this message.
     */
    public function replies()
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    /**
     * Optional attached resource.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
