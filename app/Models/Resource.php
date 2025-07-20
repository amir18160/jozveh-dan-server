<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // Import Storage facade for file_url

// Import related models for clarity in relationship definitions
use App\Models\User;
use App\Models\Category;
use App\Models\Review;

class Resource extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'description',
        'file_path',
        'user_id', // Foreign key for the user relationship
        'view_count',
        'download_count',
        'status',
        'format',
    ];


    protected $casts = [
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];


    protected $appends = ['file_url'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class, 'resource_category', 'resource_id', 'category_id');
    }


    public function reviews()
    {
        return $this->hasMany(Review::class, 'resource_id');
    }


    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->url($this->file_path);
        }
        return null;
    }
}
