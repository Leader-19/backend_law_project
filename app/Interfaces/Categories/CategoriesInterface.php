<?php

namespace App\Interfaces\Categories;

interface CategoriesInterface
{
    public function getAll();
    public function getPaginated($perPage = 10, $parentIds = []);
    public function getTree();
    public function getChildren($parentId);
    public function store($data);
    public function find($id);
    public function update($id, $data);
    public function delete($id);
}
