<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
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
}
