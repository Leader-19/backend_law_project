<?php

namespace App\Repositories\Documents;

use App\Interfaces\Documents\DocumentsInterface;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentsRepository implements DocumentsInterface
{
    public function getAll(): Collection
    {
        return Document::with('category')->get();
    }

    public function getPaginated(int $perPage = 10, int $page = 1, ?string $search = '', array $categoryIds = [], string $searchType = 'all'): LengthAwarePaginator
    {
        $search = (string) ($search ?? '');
        $query = Document::with('category')->orderBy('created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search, $searchType) {
                if ($searchType === 'name') {
                    $q->where('doc_name', 'like', "%{$search}%");
                } elseif ($searchType === 'title') {
                    $q->where('doc_title', 'like', "%{$search}%");
                } elseif ($searchType === 'description') {
                    $q->where('description', 'like', "%{$search}%");
                } else {
                    $q->where('doc_name', 'like', "%{$search}%")
                        ->orWhere('doc_title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            });
        }

        if ($categoryIds !== []) {
            $query->whereIn('category_id', $categoryIds);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function store(array $data): \Illuminate\Database\Eloquent\Model
    {
        return Document::create($data);
    }

    public function find(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return Document::with('category')->findOrFail($id);
    }

    public function update(int|string $id, array $data): \Illuminate\Database\Eloquent\Model
    {
        $document = Document::findOrFail($id);
        $document->update($data);

        return $document;
    }

    public function delete(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        $document = Document::findOrFail($id);
        $document->delete();

        return $document;
    }
}
