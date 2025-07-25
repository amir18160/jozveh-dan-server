<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_id',
        'reason',
        'status',
    ];


    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }


    public function resource()
    {
        return $this->belongsTo(\App\Models\Resource::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'user_id');
    }
}
