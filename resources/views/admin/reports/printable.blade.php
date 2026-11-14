<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} &middot; {{ $clinic['clinic_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,600;0,7..72,700;1,7..72,600&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F8F6F2;
            color: #2E3230;
            font-size: 12px;
            line-height: 1.45;
            padding: 24px;
        }

        /* Screen Control Bar (Never Printed) */
        .no-print {
            display: block;
        }
        .control-bar {
            max-width: 1100px;
            margin: 0 auto 20px auto;
            background: #2E3230;
            color: #FFFFFF;
            padding: 12px 24px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .control-bar .info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .control-bar .actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-ctrl {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 9999px;
            font-family: 'Nunito Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
            border: none;
        }
        .btn-ctrl-primary {
            background: #4A7C59;
            color: #FFFFFF;
        }
        .btn-ctrl-primary:hover {
            background: #3d6b4a;
        }
        .btn-ctrl-secondary {
            background: rgba(255,255,255,0.15);
            color: #FFFFFF;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-ctrl-secondary:hover {
            background: rgba(255,255,255,0.25);
        }
        .btn-ctrl-close {
            background: transparent;
            color: #C4C0B6;
        }
        .btn-ctrl-close:hover {
            color: #FFFFFF;
        }

        /* Printable Document Sheet */
        .report-sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 36px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid rgba(196,200,188,0.4);
        }

        /* Branded Clinic Header */
        .clinic-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #4A7C59;
            margin-bottom: 24px;
        }
        .clinic-brand {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .clinic-logo {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #C8E8D0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .clinic-logo-fallback {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #C8E8D0;
            color: #2A6038;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            flex-shrink: 0;
        }
        .clinic-title h1 {
            font-family: 'Literata', serif;
            font-size: 22px;
            font-weight: 700;
            color: #2E3230;
            margin-bottom: 3px;
        }
        .clinic-title .tagline {
            font-size: 12px;
            color: #6B6358;
            font-style: italic;
            margin-bottom: 4px;
        }
        .clinic-title .branch-line {
            font-size: 11px;
            color: #4A7C59;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .clinic-contact {
            text-align: right;
            font-size: 11px;
            color: #555;
            line-height: 1.5;
        }
        .clinic-contact strong {
            color: #2E3230;
        }

        /* Document Title & Meta Box */
        .document-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #EAE6DE;
        }
        .document-title h2 {
            font-family: 'Literata', serif;
            font-size: 20px;
            font-weight: 700;
            color: #2E3230;
            margin-bottom: 4px;
        }
        .document-title .subtitle {
            font-size: 12px;
            color: #6B6358;
        }
        .document-meta {
            text-align: right;
            font-size: 11px;
            color: #6B6358;
            line-height: 1.5;
        }
        .document-meta strong {
            color: #2E3230;
        }

        /* Data Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 24px;
        }
        .report-table thead {
            display: table-header-group;
        }
        .report-table th {
            background-color: #F5F1EA;
            color: #2E3230;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 10px;
            padding: 10px 8px;
            border: 1px solid #D5D0C5;
            text-align: left;
            white-space: nowrap;
        }
        .report-table td {
            padding: 9px 8px;
            border: 1px solid #E4E0D6;
            vertical-align: top;
            color: #2E3230;
        }
        .report-table tbody tr:nth-child(even) {
            background-color: #FAF8F5;
        }
        .report-table tbody tr {
            page-break-inside: avoid;
        }

        .badge-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: capitalize;
            border: 1px solid transparent;
        }
        .badge-pending { background: #F8E0A8; color: #554020; border-color: #E6CC88; }
        .badge-approved { background: #C8E8D0; color: #2A6038; border-color: #A6D4B2; }
        .badge-completed { background: #EAE6DE; color: #4A4E4A; border-color: #D2CDC2; }
        .badge-cancelled { background: #FFDAD8; color: #690005; border-color: #F2B8B5; }

        /* Document Footer */
        .report-footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #EAE6DE;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10.5px;
            color: #6B6358;
        }
        .report-footer .signoff {
            font-style: italic;
        }

        /* PRINT MEDIA STYLES */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
                color: #000000 !important;
                font-size: 10pt;
            }
            .report-sheet {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .report-table th {
                background-color: #EEEEEE !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .report-table tbody tr:nth-child(even) {
                background-color: #F9F9F9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge-pill {
                border: 1px solid #888 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                size: auto;
                margin: 12mm 10mm;
            }
        }
    </style>
</head>
<body>

    {{-- Screen-only Control Bar --}}
    <div class="control-bar no-print">
        <div class="info">
            <i class="fas fa-file-invoice text-success" style="font-size: 18px; color: #C8E8D0;"></i>
            <span>{{ $title }} &middot; <strong>{{ count($records) }}</strong> record(s)</span>
        </div>
        <div class="actions">
            <button type="button" class="btn-ctrl btn-ctrl-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn-ctrl btn-ctrl-secondary">
                <i class="fas fa-file-excel"></i> Export to CSV (Excel)
            </a>
            <button type="button" class="btn-ctrl btn-ctrl-close" onclick="window.close()">
                <i class="fas fa-times"></i> Close Window
            </button>
        </div>
    </div>

    {{-- Report Sheet --}}
    <div class="report-sheet">

        {{-- Branded Clinic Header --}}
        <div class="clinic-header">
            <div class="clinic-brand">
                @php
                    $printableLogo = !empty($clinic['logo_url']) ? $clinic['logo_url'] : \App\Models\ClinicSetting::getLogoUrl();
                @endphp
                <img src="{{ $printableLogo }}" alt="Clinic Logo" class="clinic-logo"
                     onerror="this.onerror=null; this.src='{{ asset('images/clinic-logo.png') }}';">
                <div class="clinic-title">
                    <h1>{{ $clinic['clinic_name'] }}</h1>
                    <div class="tagline">{{ $clinic['tagline'] }}</div>
                    <div class="branch-line">
                        <i class="fas fa-location-dot me-1"></i> {{ $clinic['branch_title'] }}
                    </div>
                </div>
            </div>
            <div class="clinic-contact">
                <div><strong>Address:</strong> {{ $clinic['address'] }}</div>
                <div><strong>Contact:</strong> {{ $clinic['phone'] }}</div>
                <div><strong>Accreditation:</strong> {{ $clinic['accreditation'] }}</div>
            </div>
        </div>

        {{-- Document Header --}}
        <div class="document-header">
            <div class="document-title">
                <h2>{{ $title }}</h2>
                <div class="subtitle">
                    {{ $subtitle ?? 'Official Clinic Records & Operations Registry' }}
                </div>
            </div>
            <div class="document-meta">
                <div><strong>Generated On:</strong> {{ $clinic['generated_at'] }}</div>
                <div><strong>Generated By:</strong> {{ $clinic['generated_by'] }}</div>
                <div><strong>Total Count:</strong> {{ count($records) }} item(s)</div>
            </div>
        </div>

        {{-- Content Table per Module --}}
        @if($reportType === 'appointments')
            {{-- APPOINTMENTS REPORT TABLE --}}
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 14%;">Date & Time</th>
                        <th style="width: 12%;">Pet</th>
                        <th style="width: 14%;">Owner & Contact</th>
                        <th style="width: 13%;">Branch</th>
                        <th style="width: 15%;">Reason / Service</th>
                        <th style="width: 11%;">Status</th>
                        <th style="width: 18%;">Notes / Reason Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $index => $appt)
                        @php
                            $st = strtolower($appt->status ?? 'pending');
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $appt->appointment_date ? \Carbon\Carbon::parse($appt->appointment_date)->format('M d, Y') : '—' }}</strong>
                                <br>
                                <span style="color: #666; font-size: 10px;">{{ $appt->formatted_time }}</span>
                            </td>
                            <td>
                                <strong>{{ $appt->pet->name ?? 'Unknown Pet' }}</strong>
                                <br>
                                <span style="color: #666; font-size: 10px;">{{ $appt->pet->species ?? '' }}{{ $appt->pet->breed ? ' · ' . $appt->pet->breed : '' }}</span>
                            </td>
                            <td>
                                <strong>{{ $appt->user->name ?? 'Unknown Owner' }}</strong>
                                <br>
                                <span style="color: #666; font-size: 10px;">{{ $appt->user->phone_number ?? $appt->user->email ?? '—' }}</span>
                            </td>
                            <td>{{ $appt->branch ?? 'N/A' }}</td>
                            <td>{{ $appt->reason ?: 'Consultation' }}</td>
                            <td>
                                <span class="badge-pill badge-{{ $st }}">{{ ucfirst($st) }}</span>
                            </td>
                            <td>
                                <span style="font-size: 10px; color: #444;">{{ $appt->notes ?: '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: #666;">
                                No appointments found for this report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'pets')
            {{-- PETS REPORT TABLE --}}
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 15%;">Pet Name</th>
                        <th style="width: 12%;">Species</th>
                        <th style="width: 14%;">Breed</th>
                        <th style="width: 8%;">Age</th>
                        <th style="width: 9%;">Weight</th>
                        <th style="width: 8%;">Gender</th>
                        <th style="width: 15%;">Owner Name & Phone</th>
                        <th style="width: 16%;">Medical Conditions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $index => $pet)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $pet->name }}</strong></td>
                            <td>{{ ucfirst($pet->species ?: 'Other') }}</td>
                            <td>{{ $pet->breed ?: 'Not specified' }}</td>
                            <td>{{ $pet->age !== null ? $pet->age . ' yrs' : '—' }}</td>
                            <td>{{ $pet->weight !== null ? number_format($pet->weight, 1) . ' kg' : '—' }}</td>
                            <td>{{ ucfirst($pet->gender ?: '—') }}</td>
                            <td>
                                <strong>{{ $pet->owner->name ?? 'Unknown' }}</strong>
                                <br>
                                <span style="color: #666; font-size: 10px;">{{ $pet->owner->phone_number ?? $pet->owner->email ?? '—' }}</span>
                            </td>
                            <td>
                                <span style="font-size: 10px; color: #555;">{{ $pet->medical_conditions ?: 'None recorded' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 24px; color: #666;">
                                No pets found for this report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'medical_records')
            {{-- MEDICAL RECORDS REPORT TABLE --}}
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 14%;">Pet & Owner</th>
                        <th style="width: 12%;">Veterinarian</th>
                        <th style="width: 18%;">Diagnosis</th>
                        <th style="width: 14%;">Vaccinations</th>
                        <th style="width: 16%;">Treatment / Notes</th>
                        <th style="width: 11%;">Next Visit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $index => $mr)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $mr->consultation_date ? $mr->consultation_date->format('M d, Y') : '—' }}</strong>
                            </td>
                            <td>
                                <strong>{{ $mr->pet->name ?? 'Unknown Pet' }}</strong>
                                <br>
                                <span style="color: #666; font-size: 10px;">Owner: {{ $mr->pet->owner->name ?? 'Unknown' }}</span>
                            </td>
                            <td>{{ $mr->veterinarian ?: 'Clinic Staff' }}</td>
                            <td>
                                <strong>{{ $mr->diagnosis ?: 'Routine Checkup' }}</strong>
                            </td>
                            <td>
                                <span style="font-size: 10px;">{{ $mr->vaccinations ?: '—' }}</span>
                            </td>
                            <td>
                                <span style="font-size: 10px; color: #444;">{{ $mr->treatment ?: ($mr->notes ?: '—') }}</span>
                            </td>
                            <td>
                                {{ $mr->next_visit_date ? $mr->next_visit_date->format('M d, Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: #666;">
                                No medical records found for this report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'pet_owners')
            {{-- PET OWNERS REPORT TABLE --}}
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 22%;">Client Name</th>
                        <th style="width: 23%;">Email Address</th>
                        <th style="width: 16%;">Phone Number</th>
                        <th style="width: 10%; text-align: center;">Pets</th>
                        <th style="width: 12%; text-align: center;">Appointments</th>
                        <th style="width: 14%;">Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $index => $owner)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $owner->name }}</strong></td>
                            <td>{{ $owner->email ?: '—' }}</td>
                            <td>{{ $owner->phone_number ?: '—' }}</td>
                            <td style="text-align: center;">
                                <strong>{{ $owner->pets_count ?? $owner->pets->count() }}</strong>
                            </td>
                            <td style="text-align: center;">
                                <strong>{{ $owner->appointments_count ?? $owner->appointments->count() }}</strong>
                            </td>
                            <td>
                                {{ $owner->created_at ? $owner->created_at->format('M d, Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: #666;">
                                No pet owners found for this report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        {{-- Document Footer --}}
        <div class="report-footer">
            <div class="signoff">
                Certified official documentation issued by {{ $clinic['clinic_name'] }}.
            </div>
            <div>
                Printed Page Document &bull; VetBook Clinic Management System
            </div>
        </div>

    </div>

    {{-- Automatic Print Trigger --}}
    <script>
        window.addEventListener('load', function() {
            // Auto open print dialog unless requested with &noprint=1
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('noprint')) {
                setTimeout(function() {
                    window.print();
                }, 400);
            }
        });
    </script>
</body>
</html>

