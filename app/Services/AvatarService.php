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
    private const MAX_DIMENSION = 320;
    private const JPEG_QUALITY = 78;
    private const SKIP_OPTIMIZE_MAX_BYTES = 51200; // Preserve already-tiny <=50KB images inside 320x320

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

            [$optimized, $extension, $mimeSubtype] = self::optimizeAvatar($contents, $extension, $mimeSubtype);

            self::cacheToPublicDisk($folder, $prefix, $extension, $optimized);

            $dataUri = 'data:image/' . $mimeSubtype . ';base64,' . base64_encode($optimized);
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
                [$optimized, $extension, $mimeSubtype] = self::optimizeAvatar($decoded, $extension, $mimeSubtype);
                self::cacheToPublicDisk($folder, $prefix, $extension, $optimized);

                // Return the data URI directly so PostgreSQL persists the photo across ephemeral container restarts
                $dataUri = 'data:image/' . $mimeSubtype . ';base64,' . base64_encode($optimized);
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
     * Compress an existing Base64 image data URI when larger than threshold and GD is available.
     */
    public static function optimizeDataUri(string $dataUri): string
    {
        $trimmed = trim($dataUri);
        if ($trimmed === '' || !preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,(.+)$/s', $trimmed, $matches)) {
            return $dataUri;
        }

        $imageType = strtolower($matches[1]);
        if (!in_array($imageType, self::ALLOWED_EXTENSIONS, true)) {
            return $dataUri;
        }

        $base64Data = preg_replace('/\s+/', '', $matches[2]);
        $decoded = base64_decode($base64Data, true);

        if ($decoded === false || $decoded === '' || strlen($decoded) > self::MAX_BYTES) {
            return $dataUri;
        }

        if (strlen($decoded) <= self::SKIP_OPTIMIZE_MAX_BYTES) {
            return $dataUri;
        }

        $extension = $imageType === 'jpeg' ? 'jpg' : $imageType;
        $mimeSubtype = $imageType === 'jpg' ? 'jpeg' : $imageType;

        [$optimized, , $outMime] = self::optimizeAvatar($decoded, $extension, $mimeSubtype);

        $result = 'data:image/' . $outMime . ';base64,' . base64_encode($optimized);

        return strlen($result) < strlen($dataUri) ? $result : $dataUri;
    }

    /**
     * Proportionally resize & compress oversized avatar binaries within 320x320 via GD when available.
     *
     * @return array{0: string, 1: string, 2: string} [binary, extension, mimeSubtype]
     */
    private static function optimizeAvatar(string $binary, string $extension, string $mimeSubtype): array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
            return [$binary, $extension, $mimeSubtype];
        }

        try {
            $src = @imagecreatefromstring($binary);
            if ($src === false) {
                return [$binary, $extension, $mimeSubtype];
            }

            $origW = imagesx($src);
            $origH = imagesy($src);
            if ($origW <= 0 || $origH <= 0) {
                return [$binary, $extension, $mimeSubtype];
            }

            if ($origW <= self::MAX_DIMENSION && $origH <= self::MAX_DIMENSION && strlen($binary) <= self::SKIP_OPTIMIZE_MAX_BYTES) {
                return [$binary, $extension, $mimeSubtype];
            }

            $scale = min(self::MAX_DIMENSION / $origW, self::MAX_DIMENSION / $origH, 1.0);
            $targetW = max(1, (int) round($origW * $scale));
            $targetH = max(1, (int) round($origH * $scale));

            $dst = imagecreatetruecolor($targetW, $targetH);
            if ($dst === false) {
                return [$binary, $extension, $mimeSubtype];
            }

            $outExt = $extension;
            $outMime = $mimeSubtype;
            $preserveAlpha = in_array($extension, ['png', 'gif', 'webp'], true);

            if ($preserveAlpha) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

            ob_start();
            $encodedOk = false;

            if ($extension === 'gif' && function_exists('imagegif')) {
                $encodedOk = imagegif($dst);
                $outExt = 'gif';
                $outMime = 'gif';
            } elseif ($extension === 'webp' && function_exists('imagewebp')) {
                $encodedOk = imagewebp($dst, null, self::JPEG_QUALITY);
                $outExt = 'webp';
                $outMime = 'webp';
            } elseif ($extension === 'png' && function_exists('imagepng')) {
                $encodedOk = imagepng($dst, null, 6);
                $outExt = 'png';
                $outMime = 'png';
            } elseif (function_exists('imagejpeg')) {
                $encodedOk = imagejpeg($dst, null, self::JPEG_QUALITY);
                $outExt = 'jpg';
                $outMime = 'jpeg';
            }

            $encoded = ob_get_clean();

            if (
                $encodedOk &&
                is_string($encoded) &&
                $encoded !== '' &&
                ($origW > self::MAX_DIMENSION || $origH > self::MAX_DIMENSION || strlen($encoded) < strlen($binary))
            ) {
                return [$encoded, $outExt, $outMime];
            }
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }
            Log::warning('Avatar GD compression skipped: ' . $e->getMessage());
        }

        return [$binary, $extension, $mimeSubtype];
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
