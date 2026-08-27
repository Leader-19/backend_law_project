<?php

namespace App\Repositories\Categories;

use App\Interfaces\Categories\CategoriesInterface;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoriesRepository implements CategoriesInterface
{
    public function getAll(): Collection
    {
        return Category::all();
    }

    public function getPaginated(int $perPage = 10, array $parentIds = []): LengthAwarePaginator
    {
        $query = Category::with('parent')->withCount('documents');

        if ($parentIds !== []) {
            $query->whereIn('parent_id', $parentIds);
        }

        return $query->paginate($perPage);
    }

    public function getTree(): Collection
    {
        return Category::with('childrenRecursive')->whereNull('parent_id')->get();
    }

    public function getChildren(int $parentId): Collection
    {
        return Category::with('documents')->where('parent_id', $parentId)->get();
    }

    public function store(array $data): \Illuminate\Database\Eloquent\Model
    {
        return Category::create($data);
    }

    public function find(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return Category::with('childrenRecursive')->findOrFail($id);
    }

    public function update(int|string $id, array $data): \Illuminate\Database\Eloquent\Model
    {
        $category = Category::findOrFail($id);
        $data['description'] = $data['description'] ?? '';
        $category->update($data);

        return $category;
    }

    public function delete(int|string $id): bool
    {
        return (bool) Category::destroy($id);
    }
}
