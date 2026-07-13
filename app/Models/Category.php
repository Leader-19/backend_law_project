<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        // Documents cascade-delete at the DB level (onDelete('cascade')),
        // which bypasses the Document model events - clean up their files here
        // so they are not orphaned on disk.
        static::deleting(function (Category $category) {
            foreach ($category->documents as $document) {
                if ($document->doc_upload) {
                    Storage::disk('public')->delete($document->doc_upload);
                }
                if ($document->image) {
                    Storage::disk('public')->delete($document->image);
                }
            }
        });
    }

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
