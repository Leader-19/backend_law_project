<?php

namespace App\Interfaces\Categories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoriesInterface
{
    public function getAll(): Collection;

    public function getPaginated(int $perPage = 10, array $parentIds = []): LengthAwarePaginator;

    public function getTree(): Collection;

    public function getChildren(int $parentId): Collection;

    public function store(array $data): Model;

    public function find(int|string $id): Model;

    public function update(int|string $id, array $data): Model;

    public function delete(int|string $id): bool;
}
