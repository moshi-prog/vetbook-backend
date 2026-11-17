<?php

namespace App\Services;

use App\Models\ClinicSetting;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Get clinic metadata for report branding.
     */
    public static function getClinicMetadata(?string $branch = null): array
    {
        $clinicName = ClinicSetting::get('clinic_name', 'BK Petaholic Animal Clinic');
        $tagline = ClinicSetting::get('clinic_tagline', 'Compassionate care for your beloved animal companions');
        $accreditation = ClinicSetting::get('clinic_accreditation', 'BAI-RVD-2024-0089');
        $logoUrl = ClinicSetting::getLogoUrl();

        $currentBranch = $branch ?? (auth()->check() ? auth()->user()->branch : null);

        // Live branch addresses & contact numbers
        $mabiniPhone = ClinicSetting::get('branch_mabini_phone', '0917-123-4567');
        $batangasPhone = ClinicSetting::get('branch_batangas_phone', '0918-765-4321');
        $mabiniAddress = ClinicSetting::get('branch_mabini_address', '142 Mabini St., Poblacion, Mabini, Batangas');
        $batangasAddress = ClinicSetting::get('branch_batangas_address', 'N.F. Almario Bldg, Kumintang Ilaya, Batangas City');

        // Branch address, phone, and display name
        $address = "{$mabiniAddress} & {$batangasAddress}";
        $phone = ClinicSetting::get('clinic_phone', '+63 917 123 4567');
        $branchTitle = 'All Branches';

        if ($currentBranch) {
            $branchTitle = $currentBranch;
            if (stripos($currentBranch, 'batangas') !== false) {
                $address = $batangasAddress;
                $phone = $batangasPhone;
            } else {
                $address = $mabiniAddress;
                $phone = $mabiniPhone;
            }
        }

        return [
            'clinic_name'   => $clinicName,
            'tagline'       => $tagline,
            'accreditation' => $accreditation,
            'logo_url'      => $logoUrl,
            'branch_title'  => $branchTitle,
            'address'       => $address,
            'phone'         => $phone,
            'generated_by'  => auth()->check() ? auth()->user()->name : 'System Admin',
            'generated_at'  => now()->format('F j, Y, g:i A'),
        ];
    }

    /**
     * Return a streamed text/csv download response with UTF-8 BOM.
     */
    public static function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->stream(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ]);
    }
}

