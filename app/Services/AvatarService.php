<?php

namespace App\Services;

use App\Models\ClinicSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AvatarService
{
    private const ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png', 'webp', 'gif'];
    private const MAX_BYTES = 8 * 1024 * 1024; // 8 MB

    /**
     * Persist avatar image data as a durable data URI in the database while
     * best-effort caching a copy on public disk for local/admin access.
     *
     * @param Request $request
     * @param string $folder e.g. 'avatars' or 'pet_avatars'
     * @param string $prefix e.g. 'user_1' or 'pet_1'
     * @return array [bool $success, ?string $avatarUrl, ?string $error]
     */
    public static function processAvatar(Request $request, string $folder = 'avatars', string $prefix = 'img'): array
    {
        // 1. Check if multipart file upload
        if ($request->hasFile('avatar')) {
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:8192',
            ]);

            if ($validator->fails()) {
                return [false, null, $validator->errors()->first('avatar')];
            }

            $file = $request->file('avatar');
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false || $contents === '') {
                return [false, null, 'Could not read uploaded image file.'];
            }

            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
            $extension = in_array($ext, self::ALLOWED_EXTENSIONS, true) ? $ext : 'jpg';
            $mimeSubtype = $extension === 'jpg' ? 'jpeg' : $extension;

            self::cacheToPublicDisk($folder, $prefix, $extension, $contents);

            $dataUri = 'data:image/' . $mimeSubtype . ';base64,' . base64_encode($contents);
            return [true, $dataUri, null];
        }

        // 2. Check if Base64 Data URL or HTTP(S) string in avatar or avatar_url
        $data = $request->input('avatar') ?: $request->input('avatar_url');

        if (!empty($data) && is_string($data)) {
            $trimmed = trim($data);

            // Base64 data URL
            if (preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,(.+)$/s', $trimmed, $matches)) {
                $imageType = strtolower($matches[1]);
                if (!in_array($imageType, self::ALLOWED_EXTENSIONS, true)) {
                    return [false, null, 'Unsupported image format. Allowed: jpeg, jpg, png, webp, gif.'];
                }

                $base64Data = preg_replace('/\s+/', '', $matches[2]);
                $decoded = base64_decode($base64Data, true);

                if ($decoded === false || $decoded === '') {
                    return [false, null, 'Invalid base64 image data.'];
                }

                if (strlen($decoded) > self::MAX_BYTES) {
                    return [false, null, 'The avatar may not be greater than 8192 kilobytes.'];
                }

                $extension = $imageType === 'jpeg' ? 'jpg' : $imageType;
                $mimeSubtype = $imageType === 'jpg' ? 'jpeg' : $imageType;
                self::cacheToPublicDisk($folder, $prefix, $extension, $decoded);

                // Return the data URI directly so PostgreSQL persists the photo across ephemeral container restarts
                $dataUri = 'data:image/' . $mimeSubtype . ';base64,' . $base64Data;
                return [true, $dataUri, null];
            }

            // Regular HTTP/HTTPS URL
            if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
                return [true, ClinicSetting::normalizeProtocol($trimmed), null];
            }
        }

        return [false, null, 'An avatar image file or valid image data string is required.'];
    }

    /**
     * Best-effort write to the public disk for local/admin caching; failures do not block DB persistence.
     */
    private static function cacheToPublicDisk(string $folder, string $prefix, string $extension, string $binary): void
    {
        try {
            $filename = "{$folder}/{$prefix}_" . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            Storage::disk('public')->put($filename, $binary);
        } catch (\Throwable $e) {
            Log::warning('Avatar public disk cache failed: ' . $e->getMessage());
        }
    }
}
