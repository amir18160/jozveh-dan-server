<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Resource;

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


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
