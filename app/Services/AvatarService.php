<?php

namespace App\Services;

use App\Models\ClinicSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AvatarService
{
    /**
     * Store avatar from either an uploaded multipart file, a base64 data URL, or a direct URL.
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

            $path = $request->file('avatar')->store($folder, 'public');
            $url = ClinicSetting::normalizeProtocol(asset('storage/' . $path));
            return [true, $url, null];
        }

        // 2. Check if Base64 Data URL or string in avatar or avatar_url
        $data = $request->input('avatar') ?: $request->input('avatar_url');

        if (!empty($data) && is_string($data)) {
            // Base64 data URL
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
                $imageType = strtolower($matches[1]);
                $base64Data = substr($data, strpos($data, ',') + 1);
                $decoded = base64_decode($base64Data);

                if ($decoded === false) {
                    return [false, null, 'Invalid base64 image data.'];
                }

                $extension = in_array($imageType, ['jpeg', 'jpg', 'png', 'webp', 'gif']) ? $imageType : 'jpg';
                $filename = "{$folder}/{$prefix}_" . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                Storage::disk('public')->put($filename, $decoded);

                $url = ClinicSetting::normalizeProtocol(asset('storage/' . $filename));
                return [true, $url, null];
            }

            // Regular HTTP/HTTPS URL
            if (str_starts_with($data, 'http://') || str_starts_with($data, 'https://')) {
                return [true, ClinicSetting::normalizeProtocol($data), null];
            }
        }

        return [false, null, 'An avatar image file or valid image data string is required.'];
    }
}
