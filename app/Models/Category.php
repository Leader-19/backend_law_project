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
     * Uses iterative approach with depth limit to prevent infinite recursion.
     */
    public function childrenRecursive(int $maxDepth = 10)
    {
        $allDescendants = collect();
        $pending = $this->children()->get()->each(fn ($child) => $allDescendants->push($child));

        $depth = 1;
        while ($pending->isNotEmpty() && $depth < $maxDepth) {
            $childrenOfPending = Category::whereIn('parent_id', $pending->pluck('id'))
                ->get()
                ->each(fn ($child) => $allDescendants->push($child));
            $pending = $childrenOfPending;
            $depth++;
        }

        return $allDescendants;
    }

    // Category belongs to parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Category belongs to many Users with specific permissions
    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user')->withPivot('permission');
    }

    /** Teams are application roles assigned to this category. */
    public function teams()
    {
        return $this->belongsToMany(Role::class, 'category_role')
            ->withPivot('permission')
            ->withTimestamps();
    }

    // Users who can view this category
    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'category_user')
            ->wherePivot('permission', 'view')
            ->orWherePivot('permission', 'manage');
    }

    // Get all ancestor categories up to root
    // Uses a batch approach: one query per tree level instead of N individual queries
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
                ->reject(fn ($id) => in_array($id, $ancestorIds))
                ->values()
                ->all();
        }

        return static::whereIn('id', $ancestorIds)->get();
    }
}
