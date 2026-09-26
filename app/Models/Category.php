<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            // Load documents once to avoid N+1
            $category->load('documents');

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Proper recursive relationship (can be eager loaded).
     * Usage: Category::with('childrenRecursive')->get()
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user')
            ->withPivot('permission');
    }

    public function teams()
    {
        return $this->belongsToMany(Role::class, 'category_role')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'category_user')
            ->wherePivotIn('permission', ['view', 'manage']);
    }

    /**
     * Get all ancestor categories up to root (iterative, safe).
     */
    public function getAllAncestors()
    {
        $ancestorIds = [];
        $pending = array_filter([$this->parent_id]);

        while (! empty($pending)) {
            $found = static::whereIn('id', $pending)
                ->select('id', 'parent_id')
                ->get();

            foreach ($found as $cat) {
                $ancestorIds[] = $cat->id;
            }

            $pending = $found->pluck('parent_id')
                ->filter()
                ->reject(fn ($id) => in_array($id, $ancestorIds, true))
                ->values()
                ->all();
        }

        return static::whereIn('id', $ancestorIds)->get();
    }

    /**
     * Get all descendant IDs (useful for permissions / bulk operations).
     */
    public function getDescendantIds(int $maxDepth = 10): array
    {
        $ids = [];
        $pending = [$this->id];
        $depth = 0;

        while (! empty($pending) && $depth < $maxDepth) {
            $children = static::whereIn('parent_id', $pending)->pluck('id')->all();
            $ids = array_merge($ids, $children);
            $pending = $children;
            $depth++;
        }

        return $ids;
    }
}
