<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    /**
     * Stream a user avatar from R2. Public and unauthenticated by design -
     * avatars are profile pictures meant to be visible wherever a user's
     * name appears (leaderboard, admin panel, etc.), the same way they were
     * reachable via the public-disk symlink before this moved to R2.
     */
    public function show(string $filename)
    {
        $path = 'avatars/'.basename($filename);

        if (! Storage::disk('s3')->exists($path)) {
            abort(404);
        }

        return Storage::disk('s3')->response($path);
    }
}
