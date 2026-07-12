<?php

namespace App\Repositories\Categories;

use App\Interfaces\Categories\CategoriesInterface;
use App\Models\Category;

class CategoriesRepository implements CategoriesInterface
{
    public function getAll()
    {
        return Category::all();
    }

    public function getPaginated($perPage = 10, $parentIds = [])
    {
        $query = Category::with('parent')->withCount('documents');

        if ($parentIds !== []) {
            $query->whereIn('parent_id', $parentIds);
        }

        return $query->paginate($perPage);
    }

    public function getTree()
    {
        return Category::with('childrenRecursive')->whereNull('parent_id')->get();
    }

    public function getChildren($parentId)
    {
        return Category::with('documents')->where('parent_id', $parentId)->get();
    }

    public function store($data)
    {
        return Category::create($data);
    }

    public function find($id)
    {
        return Category::with('childrenRecursive')->findOrFail($id);
    }

   public function update($id, $data)
{
    $category = Category::findOrFail($id);
    $data['description'] = $data['description'] ?? '';
    $category->update($data);
    return $category;
}

    public function delete($id)
    {
        return Category::destroy($id);
    }
}
