<?php

namespace App\Interfaces\Documents;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface DocumentsInterface
{
    public function getAll(): Collection;

    public function getPaginated(int $perPage = 10, int $page = 1, string $search = '', array $categoryIds = [], string $searchType = 'all'): LengthAwarePaginator;

    public function store(array $data): Model;

    public function find(int|string $id): Model;

    public function update(int|string $id, array $data): Model;

    public function delete(int|string $id): Model;
}
