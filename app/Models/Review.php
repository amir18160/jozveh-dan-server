<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_id',
        'comment',
        'status',
        'rating',
    ];

    /**
     * The user who made the review.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * The resource being reviewed.
     */
    public function resource()
    {
        return $this->belongsTo(\App\Models\Resource::class);
    }
}
