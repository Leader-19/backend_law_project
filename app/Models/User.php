<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Dom\Document;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // User can upload many Documents
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    protected static function booted(): void
    {
        // Deleting a user cascade-deletes their documents and categories at the
        // DB level, bypassing Document model events - remove the files first.
        static::deleting(function (User $user) {
            $cleanup = function ($document) {
                if ($document->doc_upload) {
                    Storage::disk('public')->delete($document->doc_upload);
                }
                if ($document->image) {
                    Storage::disk('public')->delete($document->image);
                }
            };

            foreach ($user->documents as $document) {
                $cleanup($document);
            }

            foreach ($user->categories as $category) {
                foreach ($category->documents as $document) {
                    $cleanup($document);
                }
            }
        });
    }
}
