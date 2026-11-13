<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ChunkedWebUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::create([
            'name' => 'document.create',
            'guard_name' => 'web',
        ]);

        $role = Role::create([
            'name' => 'Document Uploader',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $this->uploader = User::factory()->create();
        $this->uploader->assignRole($role);
    }

    #[Test]
    public function guest_cannot_initialize_a_web_upload(): void
    {
        $this->postJson(route('documents.upload.init'), [
            'filename' => 'contract.pdf',
            'total_size' => 100,
            'total_chunks' => 1,
        ])->assertUnauthorized();
    }

    #[Test]
    public function user_without_document_create_permission_cannot_initialize_upload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('documents.upload.init'), [
                'filename' => 'contract.pdf',
                'total_size' => 100,
                'total_chunks' => 1,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function authorized_user_can_initialize_check_and_cancel_a_web_upload(): void
    {
        $this->actingAs($this->uploader);

        $init = $this->postJson(route('documents.upload.init'), [
            'filename' => 'contract.pdf',
            'total_size' => 100,
            'total_chunks' => 1,
        ])->assertOk()
            ->assertJsonStructure(['upload_id', 'chunk_size', 'total_chunks']);

        $uploadId = $init->json('upload_id');

        $this->getJson(route('documents.upload.status', $uploadId))
            ->assertOk()
            ->assertJsonPath('upload_id', $uploadId)
            ->assertJsonPath('uploaded_chunks', 0)
            ->assertJsonPath('is_complete', false);

        $this->deleteJson(route('documents.upload.cancel', $uploadId))
            ->assertOk()
            ->assertJsonPath('message', 'Upload cancelled.');

        $this->getJson(route('documents.upload.status', $uploadId))
            ->assertNotFound();
    }

    #[Test]
    public function upload_initialization_rejects_a_chunk_count_that_does_not_match_file_size(): void
    {
        $this->actingAs($this->uploader)
            ->postJson(route('documents.upload.init'), [
                'filename' => 'contract.pdf',
                'total_size' => 5 * 1024 * 1024 + 1,
                'total_chunks' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Chunk count does not match the declared file size.');
    }
}
