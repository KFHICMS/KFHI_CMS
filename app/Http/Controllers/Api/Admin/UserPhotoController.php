<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserPhotoController extends Controller
{
    private const DISK = 'local';          // private disk: storage/app/private
    private const DIR  = 'staff-photos';

    // GET /api/admin/users/{user}/photo
    public function show(User $user)
    {
        $path = $user->staffProfile?->photo_path;

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404, 'No photo.');
        }

        return Storage::disk(self::DISK)->response($path, null, [
            'Cache-Control'          => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // POST /api/admin/users/{user}/photo   (multipart form, field name: photo)
    public function store(Request $request, User $user)
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000'],
        ]);

        $profile = $user->staffProfile;

        if (! $profile) {
            return response()->json([
                'message' => 'This user has no staff profile, so a photo cannot be added.',
            ], 422);
        }

        $oldPath = $profile->photo_path;

        // The server picks a random file name. The uploaded name is never used.
        $newPath = $request->file('photo')->store(self::DIR, self::DISK);

        try {
            DB::transaction(function () use ($request, $user, $profile, $newPath) {
                $profile->photo_path = $newPath;
                $profile->save();

                AuditLog::create([
                    'user_id'     => $request->user()->id,
                    'action'      => 'USER_PHOTO_UPDATED',
                    'entity_type' => 'User',
                    'entity_id'   => $user->id,
                    'ip_address'  => $request->ip(),
                    'details'     => "Updated photo for {$user->email}",
                ]);
            });
        } catch (\Throwable $e) {
            // Database failed: do not leave an orphan file on disk.
            Storage::disk(self::DISK)->delete($newPath);
            throw $e;
        }

        // Only after everything succeeded, remove the old file.
        if ($oldPath) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        $user->load(['roles', 'staffProfile']);

        return new UserResource($user);
    }

    // DELETE /api/admin/users/{user}/photo
    public function destroy(Request $request, User $user)
    {
        $profile = $user->staffProfile;

        if (! $profile || ! $profile->photo_path) {
            return response()->json(['message' => 'This user has no photo.'], 404);
        }

        $oldPath = $profile->photo_path;

        DB::transaction(function () use ($request, $user, $profile) {
            $profile->photo_path = null;
            $profile->save();

            AuditLog::create([
                'user_id'     => $request->user()->id,
                'action'      => 'USER_PHOTO_REMOVED',
                'entity_type' => 'User',
                'entity_id'   => $user->id,
                'ip_address'  => $request->ip(),
                'details'     => "Removed photo for {$user->email}",
            ]);
        });

        Storage::disk(self::DISK)->delete($oldPath);

        $user->load(['roles', 'staffProfile']);

        return new UserResource($user);
    }
}