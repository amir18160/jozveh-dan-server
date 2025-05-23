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

    /**
     * The user who filed the report.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * The resource being reported (nullable).
     */
    public function resource()
    {
        return $this->belongsTo(\App\Models\Resource::class);
    }
}
