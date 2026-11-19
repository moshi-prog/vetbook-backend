<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MedicalImageService
{
    public const MAX_IMAGES = 5;
    public const MAX_BYTES = 8 * 1024 * 1024; // 8 MB
    public const MAX_DIMENSION = 900;
    public const JPEG_QUALITY = 78;
    private const ALLOWED_EXTENSIONS = ['jpeg', 'jpg', 'png', 'webp', 'gif'];

    /**
     * Validate and compress a multipart UploadedFile into a persistent data URI.
     *
     * @return array{0: bool, 1: ?string, 2: ?string}
     */
    public static function processUploadedFile(UploadedFile $file): array
    {
        $validator = Validator::make(['image' => $file], [
            'image' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:8192',
        ]);

        if ($validator->fails()) {
            return [false, null, $validator->errors()->first('image')];
        }

        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false || $contents === '') {
            return [false, null, 'Could not read uploaded image file.'];
        }

        if (strlen($contents) > self::MAX_BYTES) {
            return [false, null, 'Image file size must not exceed 8 MB.'];
        }

        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $fallbackMime = $ext === 'jpg' || !in_array($ext, self::ALLOWED_EXTENSIONS, true) ? 'jpeg' : $ext;

        $dataUri = self::optimizeToJpegDataUri($contents, $fallbackMime);

        return [true, $dataUri, null];
    }

    /**
     * Validate and compress a base64 image data URI into a persistent data URI.
     *
     * @return array{0: bool, 1: ?string, 2: ?string}
     */
    public static function processBase64DataUri(string $dataUri): array
    {
        $trimmed = trim($dataUri);
        if (!preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,(.+)$/s', $trimmed, $matches)) {
            return [false, null, 'Invalid base64 image data URI format.'];
        }

        $imageType = strtolower($matches[1]);
        if (!in_array($imageType, self::ALLOWED_EXTENSIONS, true)) {
            return [false, null, 'Unsupported image format. Allowed: jpeg, jpg, png, webp, gif.'];
        }

        $base64Data = preg_replace('/\s+/', '', $matches[2]);
        $decoded = base64_decode($base64Data, true);
        if ($decoded === false || $decoded === '') {
            return [false, null, 'Invalid base64 image payload.'];
        }

        if (strlen($decoded) > self::MAX_BYTES) {
            return [false, null, 'Image size must not exceed 8 MB.'];
        }

        $fallbackMime = $imageType === 'jpg' ? 'jpeg' : $imageType;
        $compressedUri = self::optimizeToJpegDataUri($decoded, $fallbackMime);

        return [true, $compressedUri, null];
    }

    /**
     * Extract and compress 1..N uploaded images from multipart files or JSON base64 data URIs.
     *
     * @return array{0: bool, 1: array<int, string>, 2: ?string}
     */
    public static function extractImagesFromRequest(Request $request): array
    {
        // 1. Multipart file(s): images[] or single image
        $files = [];
        if ($request->hasFile('images')) {
            $rawFiles = $request->file('images');
            $files = is_array($rawFiles) ? $rawFiles : [$rawFiles];
        } elseif ($request->hasFile('image')) {
            $single = $request->file('image');
            if ($single instanceof UploadedFile) {
                $files = [$single];
            }
        }

        if (!empty($files)) {
            if (count($files) > self::MAX_IMAGES) {
                return [false, [], 'You may upload at most ' . self::MAX_IMAGES . ' images per medical record.'];
            }

            $uris = [];
            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    return [false, [], 'Invalid uploaded file entry.'];
                }
                [$ok, $uri, $err] = self::processUploadedFile($file);
                if (!$ok) {
                    return [false, [], $err ?: 'Invalid image file.'];
                }
                $uris[] = $uri;
            }

            return [true, $uris, null];
        }

        // 2. JSON array `images` or single string `image`
        $rawList = [];
        $imagesInput = $request->input('images');
        $imageInput = $request->input('image');

        if (is_array($imagesInput) && count($imagesInput) > 0) {
            $rawList = $imagesInput;
        } elseif (is_string($imageInput) && trim($imageInput) !== '') {
            $rawList = [$imageInput];
        }

        if (empty($rawList)) {
            return [false, [], 'Please select at least one image to upload.'];
        }

        if (count($rawList) > self::MAX_IMAGES) {
            return [false, [], 'You may upload at most ' . self::MAX_IMAGES . ' images per medical record.'];
        }

        $uris = [];
        foreach ($rawList as $item) {
            if (!is_string($item) || trim($item) === '') {
                return [false, [], 'Invalid image data entry.'];
            }
            [$ok, $uri, $err] = self::processBase64DataUri($item);
            if (!$ok) {
                return [false, [], $err ?: 'Invalid base64 image data.'];
            }
            $uris[] = $uri;
        }

        return [true, $uris, null];
    }

    /**
     * Resize proportionally within 900x900 and compress to JPEG quality 78 via PHP GD.
     */
    private static function optimizeToJpegDataUri(string $binary, string $fallbackMime): string
    {
        if (
            !function_exists('imagecreatefromstring') ||
            !function_exists('imagecreatetruecolor') ||
            !function_exists('imagecopyresampled') ||
            !function_exists('imagejpeg')
        ) {
            return 'data:image/' . $fallbackMime . ';base64,' . base64_encode($binary);
        }

        try {
            $src = @imagecreatefromstring($binary);
            if ($src === false) {
                return 'data:image/' . $fallbackMime . ';base64,' . base64_encode($binary);
            }

            $origW = imagesx($src);
            $origH = imagesy($src);
            if ($origW <= 0 || $origH <= 0) {
                return 'data:image/' . $fallbackMime . ';base64,' . base64_encode($binary);
            }

            $scale = min(self::MAX_DIMENSION / $origW, self::MAX_DIMENSION / $origH, 1.0);
            $targetW = max(1, (int) round($origW * $scale));
            $targetH = max(1, (int) round($origH * $scale));

            $dst = imagecreatetruecolor($targetW, $targetH);
            if ($dst === false) {
                return 'data:image/' . $fallbackMime . ';base64,' . base64_encode($binary);
            }

            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

            ob_start();
            $encodedOk = imagejpeg($dst, null, self::JPEG_QUALITY);
            $encoded = ob_get_clean();

            unset($src, $dst);

            if ($encodedOk && is_string($encoded) && $encoded !== '') {
                return 'data:image/jpeg;base64,' . base64_encode($encoded);
            }
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }
            Log::warning('MedicalImageService GD compression skipped: ' . $e->getMessage());
        }

        return 'data:image/' . $fallbackMime . ';base64,' . base64_encode($binary);
    }
}
