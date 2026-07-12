<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
     use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'parent_id',
    ];

      // Category belongs to a User (creator)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Category has many Documents
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Category has many subcategories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * All nested descendants of this category.
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // Category belongs to parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
