<?php

namespace App\Services\Categories;

use App\Interfaces\Categories\CategoriesInterface;
use Illuminate\Support\Facades\Auth;

class CategoriesService
{
    protected $repo;

    public function __construct(CategoriesInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        return $this->repo->getAll();
    }

    public function getPaginated($perPage = 10, $parentIds = [])
    {
        return $this->repo->getPaginated($perPage, $parentIds);
    }

    public function getTree()
    {
        return $this->repo->getTree();
    }

    public function getChildren($parentId)
    {
        return $this->repo->getChildren($parentId);
    }

    public function store($request)
    {
        return $this->repo->store([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id ?? null,
        ]);
    }

    public function find($id)
    {
        return $this->repo->find($id);
    }

    public function update($request, $id)
    {
        return $this->repo->update($id, [
            'title' => $request->title,
            'description' => $request->description,
            'parent_id' => $request->parent_id ?? null,
        ]);
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }
}
