@extends('admin.layouts.app')

@section('title', $pet->name)

@php
    $speciesStyles = [
        'dog'    => ['#C8E8D0', '#2A6038', 'fa-dog',    '#C8E8D0', '#2A6038'],
        'cat'    => ['#F8E0A8', '#705C30', 'fa-cat',    '#F8E0A8', '#705C30'],
        'bird'   => ['#D8E6F0', '#35607E', 'fa-dove',   '#D8E6F0', '#35607E'],
        'rabbit' => ['#FFDAD8', '#B83230', 'fa-carrot', '#FFDAD8', '#B83230'],
        'other'  => ['#EAE6DE', '#4A4E4A', 'fa-paw',    '#EAE6DE', '#4A4E4A'],
    ];
    $speciesKey = strtolower((string) ($pet->species ?? 'other'));
    $speciesConfig = $speciesStyles[$speciesKey] ?? $speciesStyles['other'];

    $avatarSrc = ($pet->avatar_url ?? null)
        ? (\Illuminate\Support\Str::startsWith($pet->avatar_url, ['http://', 'https://', '//', 'data:image/'])
            ? $pet->avatar_url
            : asset('storage/' . ltrim($pet->avatar_url, '/')))
        : null;

    $gender = strtolower((string) ($pet->gender ?? ''));
    $genderIcon = match($gender) {
        'male' => 'fa-mars',
        'female' => 'fa-venus',
        default => 'fa-genderless',
    };
    $genderColor = match($gender) {
        'male' => '#2A6038',
        'female' => '#B83230',
        default => '#4A4E4A',
    };
    $genderBg = match($gender) {
        'male' => '#C8E8D0',
        'female' => '#FFDAD8',
        default => '#EAE6DE',
    };
@endphp

@section('styles')
<style>
    .back-link {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        color: #4A7C59;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color 0.15s;
    }
    .back-link:hover {
        color: #2A6038;
    }

    .pet-banner {
        background: #F5F1EA;
        border-radius: 24px;
        padding: 28px;
        margin-bottom: 24px;
        display: flex;
        gap: 24px;
        align-items: center;
        flex-wrap: wrap;
    }
    .pet-banner h1 {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        margin: 0 0 6px;
    }
    .pet-avatar-hero {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #FFFFFF;
        box-shadow: 0 4px 16px rgba(46,50,48,0.1);
        flex-shrink: 0;
    }
    .pet-avatar-fallback {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        border: 3px solid #FFFFFF;
        box-shadow: 0 4px 16px rgba(46,50,48,0.1);
        flex-shrink: 0;
    }

    .stat-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .stat-value {
        font-family: 'Literata', serif;
        font-size: 22px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1.1;
    }
    .stat-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-top: 6px;
    }
    @media (max-width: 991px) { .stat-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .stat-row { grid-template-columns: 1fr; } }

    .section-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        margin-bottom: 24px;
    }
    .section-card-title {
        font-family: 'Literata', serif;
        font-size: 18px;
        font-weight: 700;
        color: #2E3230;
        margin: 0;
    }

    .info-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid #EAE6DE;
        font-family: 'Nunito Sans', sans-serif;
        align-items: center;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-row .k {
        width: 110px;
        font-size: 12px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-row .v {
        flex: 1;
        font-size: 14px;
        color: #2E3230;
    }

    .custom-table {
        width: 100%;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        margin: 0;
    }
    .custom-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B6358;
        padding: 12px 14px;
        background: #F5F1EA;
        border-bottom: 1px solid #EAE6DE;
        white-space: nowrap;
    }
    .custom-table td {
        padding: 14px;
        border-bottom: 1px solid #EAE6DE;
        color: #2E3230;
        vertical-align: middle;
    }
    .custom-table tbody tr {
        transition: background 0.15s;
    }
    .custom-table tbody tr:hover {
        background: rgba(245,241,234,0.6);
    }
    .custom-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
    }
    .status-pill.pending { background: #F8E0A8; color: #554020; }
    .status-pill.approved { background: #C8E8D0; color: #2A6038; }
    .status-pill.completed { background: #EAE6DE; color: #4A4E4A; }
    .status-pill.cancelled { background: #FFDAD8; color: #690005; }

    .branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 9999px;
        background: #F0ECE4;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }

    .empty-note {
        text-align: center;
        padding: 32px 16px;
        color: #6B6358;
        font-family: 'Nunito Sans', sans-serif;
    }
    .empty-note i {
        font-size: 32px;
        opacity: 0.35;
        display: block;
        margin-bottom: 10px;
    }
</style>
@endsection

@section('content')

{{-- Top Navigation & Action Buttons --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.pets.index') }}" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Pets
    </a>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.medical-records.create', ['pet_id' => $pet->id]) }}"
           style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 9999px; background: #FAF6F0; color: #2E3230; border: 1px solid #EAE6DE; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.15s;"
           onmouseover="this.style.background='#F0ECE4'" onmouseout="this.style.background='#FAF6F0'">
            <i class="fas fa-plus text-success"></i> Add Medical Record
        </a>
        <a href="{{ route('admin.medical-records.pet', $pet->id) }}"
           style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 9999px; background: #4A7C59; color: #FFFFFF; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.15s;"
           onmouseover="this.style.background='#3d6b4a'" onmouseout="this.style.background='#4A7C59'">
            <i class="fas fa-file-medical"></i> View Medical Records
        </a>
        <button type="button"
                data-bs-toggle="modal" data-bs-target="#deletePetModal"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 9999px; background: #FFDAD8; color: #B83230; border: 1px solid rgba(184,50,48,0.3); font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s;"
                onmouseover="this.style.background='#ffc4c2'" onmouseout="this.style.background='#FFDAD8'">
            <i class="fas fa-trash-alt"></i> Delete Pet
        </button>
    </div>
</div>

{{-- Pet Hero Banner --}}
<div class="pet-banner">
    @if($avatarSrc)
        <img src="{{ $avatarSrc }}" alt="{{ $pet->name }}" class="pet-avatar-hero"
             onerror="this.style.display='none'; document.getElementById('pet-avatar-fb').style.display='inline-flex';">
        <div id="pet-avatar-fb" class="pet-avatar-fallback" style="display: none; background: {{ $speciesConfig[3] }}; color: {{ $speciesConfig[4] }};">
            <i class="fas {{ $speciesConfig[2] }}"></i>
        </div>
    @else
        <div class="pet-avatar-fallback" style="background: {{ $speciesConfig[3] }}; color: {{ $speciesConfig[4] }};">
            <i class="fas {{ $speciesConfig[2] }}"></i>
        </div>
    @endif

    <div style="flex: 1; min-width: 240px;">
        <h1>{{ $pet->name }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge-chip" style="background: {{ $speciesConfig[0] }}; color: {{ $speciesConfig[1] }};">
                <i class="fas {{ $speciesConfig[2] }}"></i> {{ ucfirst($pet->species ?: 'Pet') }}
            </span>
            <span class="badge-chip" style="background: {{ $genderBg }}; color: {{ $genderColor }};">
                <i class="fas {{ $genderIcon }}"></i> {{ ucfirst($pet->gender ?: 'Unknown Gender') }}
            </span>
            @if($pet->breed)
                <span class="badge-chip" style="background: #EAE6DE; color: #4A4E4A;">
                    <i class="fas fa-paw"></i> {{ $pet->breed }}
                </span>
            @endif
            {{-- Admin Health Status Control --}}
            @php
                $healthBadge = $pet->healthBadge();
                $isAttention = $healthBadge['is_attention'];
            @endphp
            <form action="{{ route('admin.pets.update-health-status', $pet->id) }}" method="POST" class="d-inline-flex align-items-center m-0" id="adminHealthForm">
                @csrf
                <div class="badge-chip" style="background: {{ $healthBadge['bg'] }}; color: {{ $healthBadge['color'] }}; padding: 4px 10px; border: 1px solid {{ $healthBadge['color'] }}30;">
                    <i class="fas {{ $healthBadge['icon'] }} me-1"></i>
                    <select name="health_status" id="adminHealthSelect"
                            onchange="this.form.submit()"
                            aria-label="Official Clinic Health Status"
                            style="background: transparent; border: none; color: inherit; font-weight: 700; font-size: 13px; cursor: pointer; outline: none; padding-right: 4px;">
                        <option value="healthy" {{ !$isAttention ? 'selected' : '' }} style="color: #2E7D32; background: #fff;">Healthy</option>
                        <option value="attention" {{ $isAttention ? 'selected' : '' }} style="color: #B26B00; background: #fff;">Needs Attention</option>
                    </select>
                </div>
            </form>
        </div>
        <div style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358;">
            Owner:
            @if($pet->owner)
                <a href="{{ route('admin.pet-owners.show', $pet->owner->id) }}" style="color: #4A7C59; font-weight: 700; text-decoration: none;">
                    <i class="fas fa-user ms-1 me-1"></i>{{ $pet->owner->name }}
                </a>
            @else
                <span class="fst-italic">No owner linked</span>
            @endif
        </div>
    </div>
</div>

{{-- 4-Metric Summary Grid --}}
<div class="stat-row">
    {{-- Metric 1: Weight --}}
    <div class="stat">
        <div class="stat-icon" style="background: #C8E8D0; color: #2A6038;">
            <i class="fas fa-weight-scale"></i>
        </div>
        <div>
            <div class="stat-value">
                {{ $pet->weight !== null ? number_format($pet->weight, 1) . ' kg' : 'Not recorded' }}
            </div>
            <div class="stat-label">Weight</div>
        </div>
    </div>

    {{-- Metric 2: Age --}}
    <div class="stat">
        <div class="stat-icon" style="background: #F8E0A8; color: #705C30;">
            <i class="fas fa-cake-candles"></i>
        </div>
        <div>
            <div class="stat-value">
                {{ $pet->age !== null ? $pet->age . ' ' . \Illuminate\Support\Str::plural('yr', $pet->age) . ' old' : '—' }}
            </div>
            <div class="stat-label">Age</div>
        </div>
    </div>

    {{-- Metric 3: Breed --}}
    <div class="stat">
        <div class="stat-icon" style="background: #D8E6F0; color: #35607E;">
            <i class="fas fa-dna"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size: 18px; word-break: break-word;">
                {{ $pet->breed ?: 'Not specified' }}
            </div>
            <div class="stat-label">Breed</div>
        </div>
    </div>

    {{-- Metric 4: Gender --}}
    <div class="stat">
        <div class="stat-icon" style="background: #EAE6DE; color: #4A4E4A;">
            <i class="fas {{ $genderIcon }}"></i>
        </div>
        <div>
            <div class="stat-value">
                {{ ucfirst($pet->gender ?: 'Unknown') }}
            </div>
            <div class="stat-label">Gender</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Left Column: Owner Information & Medical Conditions --}}
    <div class="col-lg-4">

        {{-- Owner & Contact Info Card --}}
        <div class="section-card">
            <h3 class="section-card-title pb-3 mb-3 border-bottom d-flex align-items-center gap-2">
                <i class="fas fa-user-circle" style="color: #4A7C59;"></i> Owner Information
            </h3>
            @if($pet->owner)
                <div class="info-row">
                    <span class="k">Name</span>
                    <span class="v">
                        <a href="{{ route('admin.pet-owners.show', $pet->owner->id) }}" style="color: #4A7C59; font-weight: 700; text-decoration: none;">
                            {{ $pet->owner->name }}
                        </a>
                    </span>
                </div>
                <div class="info-row">
                    <span class="k">Phone</span>
                    <span class="v">
                        @if($pet->owner->phone_number)
                            <a href="tel:{{ $pet->owner->phone_number }}" style="color: #2E3230; text-decoration: none;">
                                <i class="fas fa-phone me-1 text-success"></i> {{ $pet->owner->phone_number }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="k">Email</span>
                    <span class="v">
                        @if($pet->owner->email)
                            <a href="mailto:{{ $pet->owner->email }}" style="color: #2E3230; text-decoration: none; word-break: break-all;">
                                <i class="fas fa-envelope me-1 text-success"></i> {{ $pet->owner->email }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="k">Registered</span>
                    <span class="v">
                        {{ $pet->owner->created_at ? $pet->owner->created_at->format('M d, Y') : '—' }}
                    </span>
                </div>

                <a href="{{ route('admin.pet-owners.show', $pet->owner->id) }}"
                   class="btn w-100 rounded-pill mt-3"
                   style="background: #F5F1EA; color: #4A7C59; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; border: 1px solid #EAE6DE; text-decoration: none;">
                    <i class="fas fa-external-link-alt me-1"></i> View Full Owner Profile
                </a>
            @else
                <div class="empty-note py-3">
                    <i class="fas fa-user-slash"></i>
                    <p style="margin: 0; font-size: 13px;">No registered owner profile linked to this pet.</p>
                </div>
            @endif
        </div>

        {{-- Medical Conditions & Allergies Card --}}
        <div class="section-card">
            <h3 class="section-card-title pb-3 mb-3 border-bottom d-flex align-items-center gap-2">
                <i class="fas fa-notes-medical" style="color: #B83230;"></i> Medical Conditions
            </h3>
            @if(filled($pet->medical_conditions))
                <div style="background: #FFF5F5; border: 1px solid rgba(184,50,48,0.25); border-radius: 16px; padding: 18px; color: #8A1C14; font-family: 'Nunito Sans', sans-serif;">
                    <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 12px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #B83230;">
                        <i class="fas fa-triangle-exclamation"></i> Chronic Conditions & Allergies
                    </div>
                    <div style="font-size: 14px; line-height: 1.6; white-space: pre-wrap; font-weight: 600;">{{ $pet->medical_conditions }}</div>
                </div>
            @else
                <div class="empty-note py-3">
                    <i class="fas fa-shield-cat"></i>
                    <p style="margin: 0; font-size: 13px;">No pre-existing conditions or allergies recorded.</p>
                </div>
            @endif
        </div>

    </div>

    {{-- Right Column: Medical History & Appointments --}}
    <div class="col-lg-8">

        {{-- Recent Medical Records --}}
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                <h3 class="section-card-title d-flex align-items-center gap-2">
                    <i class="fas fa-file-medical" style="color: #4A7C59;"></i> Recent Medical Records
                </h3>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.medical-records.create', ['pet_id' => $pet->id]) }}"
                       class="btn btn-sm"
                       style="background: #4A7C59; color: #FFFFFF; border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px; padding: 6px 14px; text-decoration: none;">
                        <i class="fas fa-plus me-1"></i> Add Record
                    </a>
                    <a href="{{ route('admin.medical-records.pet', $pet->id) }}"
                       style="font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; color: #4A7C59; text-decoration: none;">
                        View All ({{ $pet->medicalRecords->count() }}) <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            @if($pet->medicalRecords->isNotEmpty())
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Veterinarian</th>
                                <th>Diagnosis</th>
                                <th>Vaccinations</th>
                                <th>Next Visit</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pet->medicalRecords->take(5) as $record)
                                <tr>
                                    <td class="fw-bold">
                                        {{ $record->consultation_date ? $record->consultation_date->format('M d, Y') : '—' }}
                                    </td>
                                    <td>
                                        {{ $record->veterinarian ?: '—' }}
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #2E3230;">{{ $record->diagnosis ?: 'Routine Examination' }}</div>
                                        @if($record->treatment)
                                            <div style="font-size: 11px; color: #6B6358; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $record->treatment }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $record->vaccinations ?: '—' }}
                                    </td>
                                    <td>
                                        {{ $record->next_visit_date ? $record->next_visit_date->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.medical-records.show', $record->id) }}"
                                           class="btn btn-sm"
                                           style="background: #F5F1EA; color: #4A7C59; border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px; padding: 4px 12px; text-decoration: none;">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-note">
                    <i class="fas fa-file-medical"></i>
                    <p class="mb-2" style="font-weight: 600;">No medical records on file for {{ $pet->name }}.</p>
                    <a href="{{ route('admin.medical-records.create', ['pet_id' => $pet->id]) }}"
                       class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="fas fa-plus me-1"></i> Add First Record
                    </a>
                </div>
            @endif
        </div>

        {{-- Recent Appointments --}}
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                <h3 class="section-card-title d-flex align-items-center gap-2">
                    <i class="fas fa-calendar-check" style="color: #4A7C59;"></i> Recent Appointments
                </h3>
                <span style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700; background: #FAF6F0; color: #6B6358; padding: 4px 12px; border-radius: 9999px; border: 1px solid #EAE6DE;">
                    {{ $pet->appointments->count() }} total
                </span>
            </div>

            @if($pet->appointments->isNotEmpty())
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Branch</th>
                                <th>Reason</th>
                                <th>Veterinarian</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pet->appointments->take(10) as $appt)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $appt->appointment_date ? $appt->appointment_date->format('M d, Y') : '—' }}</div>
                                        <div style="font-size: 11px; color: #6B6358;">
                                            {{ $appt->formatted_time }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="branch-chip">
                                            <i class="fas fa-location-dot"></i> {{ ucfirst($appt->branch ?: 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #2E3230;">{{ $appt->reason ?: 'Consultation' }}</div>
                                        @if($appt->notes)
                                            <div style="font-size: 11px; color: #6B6358; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $appt->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $appt->veterinarian ?: '—' }}
                                    </td>
                                    <td>
                                        @php
                                            $st = strtolower($appt->status ?? 'pending');
                                        @endphp
                                        <span class="status-pill {{ $st }}">
                                            {{ ucfirst($st) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.appointments.show', $appt->id) }}"
                                           class="btn btn-sm"
                                           style="background: #F5F1EA; color: #4A7C59; border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px; padding: 4px 12px; text-decoration: none;">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-note">
                    <i class="fas fa-calendar-times"></i>
                    <p style="margin: 0; font-weight: 600;">No appointments booked for {{ $pet->name }} yet.</p>
                </div>
            @endif
        </div>

    </div>
</div>

{{-- Delete Pet Confirmation Modal --}}
<div class="modal fade" id="deletePetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <form method="POST" action="{{ route('admin.pets.destroy', $pet->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE; padding: 20px 24px;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Pet Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A; padding: 24px;">
                    <p class="mb-3">
                        Are you sure you want to permanently delete <strong>{{ $pet->name }}</strong>?
                    </p>
                    <div style="background: #FFDAD8; border: 1px solid rgba(184,50,48,0.3); border-radius: 12px; padding: 12px 16px; color: #690005; font-size: 13px;">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Warning:</strong> You are deleting Pet Profiles. This removes the pet from the owner's mobile app and deletes all appointments and medical records.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE; padding: 16px 24px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal"
                            style="font-family: 'Nunito Sans', sans-serif; font-weight: 600; font-size: 14px;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4"
                            style="background: #BA1A1A; border: none; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 14px;">
                        Delete Pet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
