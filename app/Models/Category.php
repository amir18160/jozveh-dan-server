<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $fillable = ['name', 'parent_id', 'image_path'];



    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'resource_category');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'resource_category');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
        // This eager loads children and their children, and so on.
    }

    public function getImageUrlAttribute()
    {
        return $this->image_path
            ? Storage::url($this->image_path)
            : null;
    }
}
