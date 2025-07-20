<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Group extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'description',
        'image_path',
        'owner_id', // The owner of the group
    ];


    protected $appends = ['image_url'];


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }


    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'group_id');
    }


    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }
        return null;
    }
}
