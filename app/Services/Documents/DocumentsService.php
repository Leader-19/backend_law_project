<?php

namespace App\Services\Documents;

use App\Interfaces\Documents\DocumentsInterface;
use Illuminate\Support\Facades\Storage;

class DocumentsService
{
    protected $repo;

    public function __construct(DocumentsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        return $this->repo->getAll();
    }

    public function getPaginated($perPage = 10, $page = 1, $search = '', $categoryIds = [], $searchType = 'all')
    {
        return $this->repo->getPaginated($perPage, $page, $search, $categoryIds, $searchType);
    }

    public function store($request)
    {
        $path = null;
        $imagePath = null;

        if ($request->hasFile('doc_upload')) {
            $file = $request->file('doc_upload');

            // keep original name but make it safe and unique
            $filename = safe_filename($file->getClientOriginalName());

            $path = $file->storeAs('documents', $filename, 'public');
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = safe_filename($image->getClientOriginalName(), 'image');
            $imagePath = $image->storeAs('documents/images', $imageName, 'public');
        }

        return $this->repo->store([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            'doc_name' => $request->doc_name,
            'doc_title' => $request->doc_title,
            'doc_upload' => $path,
            'image' => $imagePath,
            'description' => $request->description,
        ]);
    }

    public function find($id)
    {
        return $this->repo->find($id);
    }

    public function update($request, $id)
    {
        $document = $this->repo->find($id);

        $data = [
            'doc_name' => $request->doc_name,
            'doc_title' => $request->doc_title,
            'description' => $request->description,
            'category_id' => $request->category_id,
        ];

        if ($request->hasFile('doc_upload')) {

            // delete old file
            if ($document->doc_upload) {
                \Storage::disk('public')->delete($document->doc_upload);
            }

            $file = $request->file('doc_upload');

            // keep original name safe and unique
            $filename = safe_filename($file->getClientOriginalName());

            $data['doc_upload'] = $file->storeAs('documents', $filename, 'public');
        }

        if ($request->hasFile('image')) {
            if ($document->image) {
                \Storage::disk('public')->delete($document->image);
            }
            $image = $request->file('image');
            $imageName = safe_filename($image->getClientOriginalName(), 'image');
            $data['image'] = $image->storeAs('documents/images', $imageName, 'public');
        }

        return $this->repo->update($id, $data);
    }

    public function delete($id)
    {
        $document = $this->repo->find($id);

        if ($document->doc_upload) {
            Storage::disk('public')->delete($document->doc_upload);
        }

        if ($document->image) {
            Storage::disk('public')->delete($document->image);
        }

        return $this->repo->delete($id);
    }

    public function storeBatch($validated): int
    {
        $userId = auth()->id();
        $count = 0;

        foreach ($validated['doc_upload'] as $file) {
            $filename = safe_filename($file->getClientOriginalName());
            $path = $file->storeAs('documents', $filename, 'public');

            $this->repo->store([
                'user_id' => $userId,
                'category_id' => $validated['category_id'],
                'doc_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'doc_title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'doc_upload' => $path,
                'image' => null,
                'description' => $validated['description'] ?: null,
            ]);

            $count++;
        }

        return $count;
    }

    public function storeBatchZip($zipFile, $categoryId, $description = ''): int
    {
        $userId = auth()->id();
        $count = 0;

        $zip = new \ZipArchive;
        $tempDir = sys_get_temp_dir().'/doc_import_'.uniqid();

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $zipPath = $zipFile->getRealPath();

        if ($zip->open($zipPath) === true) {
            $files = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];

                if ($stat['size'] === 0) {
                    continue;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

                if (! in_array($ext, $allowedExts)) {
                    continue;
                }

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    continue;
                }

                $safeName = safe_filename(pathinfo($name, PATHINFO_BASENAME));
                $destPath = $tempDir.'/'.$safeName;

                file_put_contents($destPath, $content);

                $files[] = [
                    'path' => $destPath,
                    'original_name' => pathinfo($name, PATHINFO_FILENAME),
                ];
            }

            $zip->close();

            foreach ($files as $fileData) {
                $path = Storage::disk('public')->putFileAs('documents', $fileData['path'], basename($fileData['path']));

                $this->repo->store([
                    'user_id' => $userId,
                    'category_id' => $categoryId,
                    'doc_name' => $fileData['original_name'],
                    'doc_title' => $fileData['original_name'],
                    'doc_upload' => $path,
                    'image' => null,
                    'description' => $description ?: null,
                ]);

                $count++;
            }
        } else {
            throw new \RuntimeException('Unable to open the ZIP file.');
        }

        foreach (glob($tempDir.'/*') as $f) {
            @unlink($f);
        }
        @rmdir($tempDir);

        return $count;
    }
}
