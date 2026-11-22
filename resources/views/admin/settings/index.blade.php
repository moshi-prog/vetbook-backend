@extends('admin.layouts.app')

@section('title', 'Settings')

@section('styles')
<style>
    .settings-header {
        margin-bottom: 24px;
    }
    .settings-title {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 4px;
    }
    .settings-subtitle {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #6B6358;
        margin: 0;
    }

    /* Tabs Bar */
    .settings-nav {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        background: #FFFFFF;
        padding: 12px;
        border-radius: 9999px;
        border: 1px solid rgba(196,200,188,0.3);
        box-shadow: 0 4px 20px rgba(46,50,48,0.04);
    }
    .settings-tab-btn {
        padding: 10px 20px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .settings-tab-btn:hover {
        background: #EAE6DE;
        color: #2E3230;
    }
    .settings-tab-btn.active {
        background: #4A7C59;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(74,124,89,0.25);
    }

    /* Tab Panes */
    .tab-pane {
        display: none;
    }
    .tab-pane.active {
        display: block;
        animation: fadeIn 0.25s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Cards & Layout */
    .settings-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        margin-bottom: 24px;
    }
    .section-head {
        border-bottom: 1px solid #EAE6DE;
        padding-bottom: 16px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .section-title {
        font-family: 'Literata', serif;
        font-size: 20px;
        font-weight: 700;
        color: #2E3230;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-desc {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        color: #6B6358;
        margin-top: 4px;
    }

    /* Form Inputs */
    .f-label {
        display: block;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 8px;
    }
    .f-input, .f-select, .f-textarea {
        width: 100%;
        padding: 12px 18px;
        border-radius: 16px;
        border: 1px solid rgba(196,200,188,0.5);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
        transition: all 0.15s ease;
    }
    .f-input:focus, .f-select:focus, .f-textarea:focus {
        outline: none;
        border-color: #4A7C59;
        background: #FFFFFF;
        box-shadow: 0 0 0 3px rgba(74,124,89,0.12);
    }
    .f-help {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        color: #6B6358;
        margin-top: 6px;
    }

    /* Buttons */
    .btn-green-save {
        padding: 12px 28px;
        border-radius: 9999px;
        background: #4A7C59;
        color: #FFFFFF;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-green-save:hover {
        background: #3d6b4a;
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(74,124,89,0.25);
    }
    .btn-soft {
        padding: 10px 20px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-soft:hover {
        background: #EAE6DE;
        color: #2E3230;
    }

    /* Badge & Tag */
    .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }
    .badge-pill.active { background: #C8E8D0; color: #2A6038; }
    .badge-pill.warning { background: #F8E0A8; color: #705C30; }
    .badge-pill.neutral { background: #EAE6DE; color: #4A4E4A; }

    /* Custom Switch Toggle */
    .switch-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        background: #FAF6F0;
        border-radius: 16px;
        margin-bottom: 12px;
    }
    .form-check-input:checked {
        background-color: #4A7C59;
        border-color: #4A7C59;
    }

    /* Branch & List Items */
    .branch-item-card {
        background: #FAF6F0;
        border: 1px solid rgba(196,200,188,0.4);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }
    .add-branch-dashed {
        border: 2px dashed rgba(74,124,89,0.35);
        border-radius: 20px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        background: #FAF6F0;
        color: #4A7C59;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        transition: all 0.2s;
    }
    .add-branch-dashed:hover {
        background: #F0ECE4;
        border-color: #4A7C59;
    }

    /* FAQ accordion item */
    .faq-box {
        background: #FAF6F0;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 12px;
    }

    /* Admin user item */
    .admin-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
        border-bottom: 1px solid #EAE6DE;
        flex-wrap: wrap;
        gap: 12px;
    }
    .admin-item-row:last-child {
        border-bottom: none;
    }
    .user-avatar-initial {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #C8E8D0;
        color: #2A6038;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Literata', serif;
        font-weight: 700;
        font-size: 18px;
    }

    /* Danger zone */
    .danger-zone-card {
        border: 1px solid rgba(184,50,48,0.3);
        background: #FFF5F5;
        border-radius: 24px;
        padding: 24px;
        margin-top: 24px;
    }
    /* Cropper modal custom styling */
    .cropper-view-box,
    .cropper-face {
        border-radius: 50%;
    }
    .cropper-view-box {
        outline: 0;
        box-shadow: 0 0 0 2px #4A7C59;
    }
    .cropper-dashed {
        border: 1px dashed rgba(255, 255, 255, 0.6);
    }

    /* Schedule Cards & iOS Scroll-Wheel Drum Time Picker */
    .schedule-card {
        background: #FAF6F0;
        border: 1px solid rgba(196, 200, 188, 0.5);
        border-radius: 20px;
        padding: 18px;
        height: 100%;
        transition: border-color 0.2s ease, opacity 0.2s ease, background-color 0.2s ease;
    }
    .schedule-card.is-closed {
        opacity: 0.72;
        background: #F4EFE6;
    }
    .schedule-card.has-error {
        border-color: #BA1A1A;
        background: #FFF8F7;
    }
    .schedule-time-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        color: #6B6358;
        margin-bottom: 5px;
    }
    .schedule-time-btn {
        width: 100%;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px solid rgba(196, 200, 188, 0.75);
        background: #FFFFFF;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 700;
        color: #2E3230;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        cursor: pointer;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .schedule-time-btn:hover:not(:disabled) {
        border-color: #4A7C59;
        box-shadow: 0 2px 10px rgba(74, 124, 89, 0.14);
    }
    .schedule-time-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        background: #EFEBE3;
    }
    .schedule-closed-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        background: #EAE6DE;
        color: #5C5F5C;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
    }
    .schedule-error {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: #BA1A1A;
        margin-top: 10px;
    }
    .wheel-drum-wrap {
        position: relative;
        height: 220px;
        display: flex;
        align-items: stretch;
        justify-content: center;
        overflow: hidden;
        user-select: none;
        -webkit-user-select: none;
        margin: 4px 0;
    }
    .wheel-highlight-bar {
        position: absolute;
        top: 88px;
        height: 44px;
        left: 16px;
        right: 16px;
        background: #EEF1F4;
        border-radius: 10px;
        pointer-events: none;
        z-index: 1;
    }
    .wheel-fade-top {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 76px;
        background: linear-gradient(to bottom, #FFFFFF 12%, rgba(255, 255, 255, 0));
        pointer-events: none;
        z-index: 3;
    }
    .wheel-fade-bottom {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 76px;
        background: linear-gradient(to top, #FFFFFF 12%, rgba(255, 255, 255, 0));
        pointer-events: none;
        z-index: 3;
    }
    .wheel-col {
        position: relative;
        z-index: 2;
        flex: 1;
        height: 220px;
        overflow-y: auto;
        scroll-snap-type: y mandatory;
        padding-top: 88px;
        padding-bottom: 88px;
        scrollbar-width: none;
        -ms-overflow-style: none;
        text-align: center;
    }
    .wheel-col::-webkit-scrollbar {
        display: none;
    }
    .wheel-item {
        height: 44px;
        line-height: 44px;
        scroll-snap-align: center;
        cursor: pointer;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 19px;
        color: #9CA3AF;
        opacity: 0.45;
        transition: color 0.1s ease;
    }
    .wheel-action-btn {
        background: transparent;
        border: none;
        padding: 8px 14px;
        border-radius: 10px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .wheel-action-btn.cancel {
        color: #6B7280;
    }
    .wheel-action-btn.cancel:hover {
        background: #F3F4F6;
        color: #111827;
    }
    .wheel-action-btn.ok {
        color: #4A7C59;
    }
    .wheel-action-btn.ok:hover {
        background: #EBF3ED;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
@endsection

@section('content')

{{-- Header --}}
<div class="settings-header">
    <h2 class="settings-title">Settings</h2>
    <p class="settings-subtitle">Manage clinic profile, branch locations, AI assistant, SMS alerts, and system parameters.</p>
</div>

{{-- Navigation Tabs --}}
<div class="settings-nav">
    <button type="button" class="settings-tab-btn active" data-tab="clinic">
        <i class="fas fa-clinic-medical"></i> Clinic Information
    </button>
    <button type="button" class="settings-tab-btn" data-tab="branches">
        <i class="fas fa-code-branch"></i> Branches
    </button>
    <button type="button" class="settings-tab-btn" data-tab="chatbot">
        <i class="fas fa-robot"></i> AI Chatbot
    </button>
    <button type="button" class="settings-tab-btn" data-tab="sms">
        <i class="fas fa-comment-sms"></i> SMS Templates
    </button>
    @if(auth()->user()->branch === null)
    <button type="button" class="settings-tab-btn" data-tab="admins">
        <i class="fas fa-users-gear"></i> Staff Accounts
    </button>
    @endif
    <button type="button" class="settings-tab-btn" data-tab="preferences">
        <i class="fas fa-user-gear"></i> Preferences &amp; Account
    </button>
</div>

{{-- ========================================================= --}}
{{-- TAB 1: CLINIC INFORMATION --}}
{{-- ========================================================= --}}
<div class="tab-pane active" id="tab-clinic">
    <form id="clinicProfileForm" method="POST" action="{{ route('admin.settings.clinic') }}" enctype="multipart/form-data">
        @csrf
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-hospital text-success"></i> Clinic Profile</h3>
                    <div class="section-desc">General identity, primary hotline, and official operating schedule.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="f-label">Clinic Name</label>
                    <input type="text" name="clinic_name" class="f-input" value="{{ $clinic['name'] }}" required>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Tagline / Mission</label>
                    <input type="text" name="tagline" class="f-input" value="{{ $clinic['tagline'] }}">
                </div>

                <div class="col-md-6">
                    <label class="f-label">Primary Contact Email</label>
                    <input type="email" name="email" class="f-input" value="{{ $clinic['email'] }}" required>
                </div>

                @php
                    $isSuperAdmin = auth()->user()->branch === null;
                    $ownBranchKey = (!$isSuperAdmin && stripos(auth()->user()->branch, 'batangas') !== false) ? 'batangas' : 'mabini';
                    $ownBranchLabel = $ownBranchKey === 'batangas' ? 'Batangas City Branch' : 'Mabini Branch';
                    $mabiniPhoneVal = \App\Models\ClinicSetting::get('branch_mabini_phone', '0917-123-4567');
                    $batangasPhoneVal = \App\Models\ClinicSetting::get('branch_batangas_phone', '0918-765-4321');
                    $mabiniAddressVal = \App\Models\ClinicSetting::get('branch_mabini_address', '142 Mabini St., Poblacion, Mabini, Batangas');
                    $batangasAddressVal = \App\Models\ClinicSetting::get('branch_batangas_address', 'N.F. Almario Bldg, Kumintang Ilaya, Batangas City');
                @endphp

                @if($isSuperAdmin)
                    <input type="hidden" name="phone" value="{{ $clinic['phone'] }}">
                    <div class="col-md-6">
                        <label class="f-label">Mabini Branch Address</label>
                        <input type="text" name="branch_mabini_address" class="f-input" value="{{ $mabiniAddressVal }}" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="f-label">Batangas City Branch Address</label>
                        <input type="text" name="branch_batangas_address" class="f-input" value="{{ $batangasAddressVal }}" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="f-label">Mabini Branch Support Hotline</label>
                        <div class="f-input d-flex align-items-center justify-content-between bg-white" style="gap: 8px;">
                            <span class="text-truncate">{{ $mabiniPhoneVal }}</span>
                            <span class="badge-pill neutral flex-shrink-0"><i class="fas fa-lock me-1"></i> Managed exclusively by Mabini Branch Staff</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="f-label">Batangas City Branch Support Hotline</label>
                        <div class="f-input d-flex align-items-center justify-content-between bg-white" style="gap: 8px;">
                            <span class="text-truncate">{{ $batangasPhoneVal }}</span>
                            <span class="badge-pill neutral flex-shrink-0"><i class="fas fa-lock me-1"></i> Managed exclusively by Batangas City Branch Staff</span>
                        </div>
                    </div>
                @else
                    <div class="col-md-6">
                        <label class="f-label">{{ $ownBranchLabel }} Contact Number</label>
                        <input type="text" name="branch_{{ $ownBranchKey }}_phone" class="f-input"
                               value="{{ $ownBranchKey === 'batangas' ? $batangasPhoneVal : $mabiniPhoneVal }}"
                               required maxlength="30">
                        <div class="f-help">Direct hotline shown to pet owners and the AI assistant for {{ $ownBranchLabel }}.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="f-label">{{ $ownBranchLabel }} Address</label>
                        <div class="f-input d-flex align-items-center justify-content-between bg-white" style="gap: 8px;">
                            <span class="text-truncate">{{ $ownBranchKey === 'batangas' ? $batangasAddressVal : $mabiniAddressVal }}</span>
                            <span class="badge-pill neutral flex-shrink-0"><i class="fas fa-lock me-1"></i> Branch address is managed exclusively by Admin</span>
                        </div>
                    </div>
                    <input type="hidden" name="phone" value="{{ $clinic['phone'] }}">
                @endif

                <div class="col-md-6">
                    <label class="f-label">Accreditation / License No.</label>
                    <input type="text" name="accreditation" class="f-input" value="{{ $clinic['accreditation'] }}">
                </div>

                <div class="col-md-6">
                    <label class="f-label">Clinic Logo</label>
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm overflow-hidden" style="width: 56px; height: 56px; color: #4A7C59; flex-shrink: 0; border: 2px solid #EAE6DE;">
                            @php
                                $settingsLogoUrl = !empty($clinic['logo_url']) ? $clinic['logo_url'] : \App\Models\ClinicSetting::getLogoUrl();
                            @endphp
                            <img id="clinicLogoPreviewImg" src="{{ $settingsLogoUrl }}" alt="Clinic Logo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('images/clinic-logo.png') }}';">
                            <i id="clinicLogoPawIcon" class="fas fa-paw fs-4" style="display: none;"></i>
                        </div>

                        @if(auth()->user()->branch === null)
                            {{-- Super Admin: Functional Choose & Crop UI --}}
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn-soft d-inline-flex align-items-center gap-1" id="triggerLogoPickerBtn">
                                        <i class="fas fa-crop-simple"></i> <span>Choose &amp; Crop Logo</span>
                                    </button>
                                    <input type="file" id="logoFileInput" accept="image/*" class="d-none">
                                    <input type="hidden" name="cropped_logo" id="croppedLogoInput" value="">
                                </div>
                                <div class="f-help mt-1" id="logoStatusText">PNG, JPG, WEBP up to 2MB. 1:1 circle crop.</div>
                            </div>
                        @else
                            {{-- Staff: Read-Only UI --}}
                            <div class="flex-grow-1">
                                <span class="badge-pill neutral"><i class="fas fa-lock me-1"></i> Read-Only</span>
                                <div class="f-help mt-1">Official clinic logo &amp; branding are managed exclusively by Admin.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color: #EAE6DE;">

            @if($isSuperAdmin)
                <h4 class="section-title mb-3" style="font-size: 16px;">
                    <i class="fas fa-clock text-success"></i> Branch Operating Schedules
                </h4>
                <div class="row g-3">
                    @foreach($branches as $b)
                        <div class="col-md-6">
                            <label class="f-label">{{ $b['name'] }} Schedule</label>
                            <div class="f-input d-flex align-items-center justify-content-between bg-white" style="gap: 8px; height: auto; min-height: 44px;">
                                <span class="small">{{ $b['hours'] }}</span>
                                <span class="badge-pill neutral flex-shrink-0"><i class="fas fa-lock me-1"></i> Managed exclusively by {{ $b['name'] }} Staff</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="f-help mt-2">Branch operating schedules and availability are managed independently by each branch staff.</div>
            @else
                <h4 class="section-title mb-3" style="font-size: 16px;">
                    <i class="fas fa-clock text-success"></i>
                    {{ auth()->user()->branch . ' Operating Schedule' }}
                </h4>
                @php
                    $fallbackSchedule = [
                        'weekday'  => ['closed' => false, 'open' => '09:00 AM', 'close' => '06:00 PM', 'formatted' => '09:00 AM - 06:00 PM'],
                        'saturday' => ['closed' => false, 'open' => '09:00 AM', 'close' => '05:00 PM', 'formatted' => '09:00 AM - 05:00 PM'],
                        'sunday'   => ['closed' => false, 'open' => '10:00 AM', 'close' => '04:00 PM', 'formatted' => '10:00 AM - 04:00 PM'],
                    ];
                    $scheduleCards = [
                        ['key' => 'weekday',  'name' => 'hours_weekday',  'title' => 'Monday – Friday', 'canClose' => false, 'slot' => $branchSchedule['weekday'] ?? $fallbackSchedule['weekday']],
                        ['key' => 'saturday', 'name' => 'hours_saturday', 'title' => 'Saturday',        'canClose' => true,  'slot' => $branchSchedule['saturday'] ?? $fallbackSchedule['saturday']],
                        ['key' => 'sunday',   'name' => 'hours_sunday',   'title' => 'Sunday',          'canClose' => true,  'slot' => $branchSchedule['sunday'] ?? $fallbackSchedule['sunday']],
                    ];
                @endphp
                <div class="row g-3">
                    @foreach($scheduleCards as $card)
                        @php $slot = $card['slot']; @endphp
                        <div class="col-lg-4">
                            <div class="schedule-card {{ $slot['closed'] ? 'is-closed' : '' }}" id="scheduleCard-{{ $card['key'] }}">
                                <input type="hidden" name="{{ $card['name'] }}" id="scheduleInput-{{ $card['key'] }}" value="{{ $slot['formatted'] }}">

                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <span class="f-label mb-0">{{ $card['title'] }}</span>
                                    @if($card['canClose'])
                                        <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                            <input class="form-check-input schedule-closed-switch" type="checkbox" role="switch"
                                                   id="scheduleClosed-{{ $card['key'] }}"
                                                   data-schedule-key="{{ $card['key'] }}"
                                                   {{ $slot['closed'] ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-bold text-secondary" for="scheduleClosed-{{ $card['key'] }}" style="cursor: pointer;">Closed</label>
                                        </div>
                                    @endif
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="schedule-time-label">OPENING TIME</div>
                                        <button type="button"
                                                class="schedule-time-btn"
                                                id="scheduleBtn-{{ $card['key'] }}-open"
                                                data-schedule-key="{{ $card['key'] }}"
                                                data-schedule-bound="open"
                                                data-schedule-label="{{ $card['title'] }} — Opening Time"
                                                data-time="{{ $slot['open'] }}"
                                                {{ $slot['closed'] ? 'disabled' : '' }}>
                                            <span class="d-inline-flex align-items-center gap-2 text-truncate">
                                                <i class="far fa-clock text-success"></i>
                                                <span class="schedule-time-value">{{ $slot['open'] }}</span>
                                            </span>
                                            <i class="fas fa-chevron-down text-muted" style="font-size: 11px;"></i>
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <div class="schedule-time-label">CLOSING TIME</div>
                                        <button type="button"
                                                class="schedule-time-btn"
                                                id="scheduleBtn-{{ $card['key'] }}-close"
                                                data-schedule-key="{{ $card['key'] }}"
                                                data-schedule-bound="close"
                                                data-schedule-label="{{ $card['title'] }} — Closing Time"
                                                data-time="{{ $slot['close'] }}"
                                                {{ $slot['closed'] ? 'disabled' : '' }}>
                                            <span class="d-inline-flex align-items-center gap-2 text-truncate">
                                                <i class="far fa-clock text-success"></i>
                                                <span class="schedule-time-value">{{ $slot['close'] }}</span>
                                            </span>
                                            <i class="fas fa-chevron-down text-muted" style="font-size: 11px;"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-2 {{ $slot['closed'] ? '' : 'd-none' }}" id="scheduleClosedPill-{{ $card['key'] }}">
                                    <span class="schedule-closed-pill"><i class="fas fa-moon"></i> Closed on this day</span>
                                </div>

                                <div class="schedule-error d-none" id="scheduleError-{{ $card['key'] }}">
                                    <i class="fas fa-circle-exclamation me-1"></i> Closing time must be after opening time.
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>

{{-- ========================================================= --}}
{{-- TAB 2: BRANCHES --}}
{{-- ========================================================= --}}
<div class="tab-pane" id="tab-branches">
    <div class="settings-card">
        <div class="section-head">
            <div>
                <h3 class="section-title"><i class="fas fa-location-dot text-success"></i> Clinic Branches</h3>
                <div class="section-desc">Manage physical clinic stations in Mabini and Batangas City.</div>
            </div>
            @if(auth()->user()->branch === null)
            <button type="button" class="btn-green-save" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="fas fa-plus"></i> Add New Branch
            </button>
            @endif
        </div>

        @foreach($branches as $branch)
            <div class="branch-item-card">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="m-0" style="font-family: 'Literata', serif; font-size: 18px; font-weight: 700; color: #2E3230;">
                            {{ $branch['name'] }}
                        </h4>
                        <span class="badge-pill active"><i class="fas fa-circle-check"></i> Active</span>
                    </div>
                    <div class="text-muted small mb-2"><i class="fas fa-map-pin me-1 text-success"></i> {{ $branch['address'] }}</div>
                    <div class="small text-secondary mb-1"><i class="fas fa-phone me-1"></i> {{ $branch['phone'] }}</div>
                    <div class="small text-secondary"><i class="fas fa-clock me-1"></i> {{ $branch['hours'] }}</div>
                </div>
                <div class="d-flex gap-2">
                    @php
                        $canEditThisBranch = auth()->user()->branch === null ||
                            ((stripos(auth()->user()->branch, 'batangas') !== false ? 'batangas' : 'mabini') === $branch['key']);
                    @endphp
                    @if($canEditThisBranch)
                    <button type="button" class="btn-soft" data-bs-toggle="modal" data-bs-target="#editBranchModal-{{ $branch['key'] }}">
                        <i class="fas fa-pen"></i> Edit
                    </button>
                    @endif
                    @if(auth()->user()->branch === null)
                    <button type="button" class="btn-soft text-danger" onclick="confirm('Are you sure you want to deactivate {{ $branch['name'] }}?')">
                        <i class="fas fa-power-off"></i> Deactivate
                    </button>
                    @endif
                </div>
            </div>
        @endforeach

        @if(auth()->user()->branch === null)
        <div class="add-branch-dashed" data-bs-toggle="modal" data-bs-target="#addBranchModal">
            <i class="fas fa-plus-circle me-1"></i> Add Another Clinic Branch Location
        </div>
        @endif
    </div>
</div>

{{-- ========================================================= --}}
{{-- TAB 3: CHATBOT --}}
{{-- ========================================================= --}}
<div class="tab-pane" id="tab-chatbot">
    <form method="POST" action="{{ route('admin.settings.chatbot') }}">
        @csrf
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-brain text-success"></i> VetBook AI Assistant</h3>
                    <div class="section-desc">Control assistant instructions, guardrails, and quick answers.</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="f-label">AI Engine Provider</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fa-brands fa-google text-primary me-2"></i> {{ $chatbot['provider'] }}</span>
                        <span class="badge-pill active">Connected</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="f-label">Foundation Model</label>
                    <div class="f-input bg-white">
                        <i class="fas fa-microchip text-success me-2"></i> {{ $chatbot['model'] }}
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="f-label">Assistant Status</label>
                    <div class="switch-wrap bg-white border">
                        <span class="small fw-bold">Active for Pet Owners</span>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" checked>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="f-label">System Instruction &amp; Clinic Guardrails</label>
                <textarea name="system_prompt" rows="5" class="f-textarea" required>{{ $chatbot['system_prompt'] }}</textarea>
                <div class="f-help">Instructs Gemini how to respond, adhere to clinic hours, and refuse veterinary prescriptions.</div>
            </div>

            <hr class="my-4" style="border-color: #EAE6DE;">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="section-title" style="font-size: 16px;"><i class="fas fa-comments text-success"></i> Structured Q&amp;A Knowledge Base</h4>
                <button type="button" class="btn-soft" onclick="addNewFaq()"><i class="fas fa-plus"></i> Add FAQ</button>
            </div>

            <div id="faqContainer">
                @foreach($chatbot['faqs'] as $index => $faq)
                    <div class="faq-box" id="faq-{{ $index }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge-pill neutral">FAQ #{{ $index + 1 }}</span>
                            <button type="button" class="btn btn-sm text-danger p-0" onclick="document.getElementById('faq-{{ $index }}').remove()">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                        <input type="text" class="f-input mb-2" value="{{ $faq['question'] }}" placeholder="Question">
                        <textarea rows="2" class="f-textarea" placeholder="Answer">{{ $faq['answer'] }}</textarea>
                    </div>
                @endforeach
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>

{{-- ========================================================= --}}
{{-- TAB 4: SMS TEMPLATES --}}
{{-- ========================================================= --}}
<div class="tab-pane" id="tab-sms">
    <form method="POST" action="{{ route('admin.settings.sms') }}">
        @csrf
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-paper-plane text-success"></i> SMS Notification Templates</h3>
                    <div class="section-desc">Automated transactional SMS dispatches to Philippine mobile numbers.</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="f-label">Gateway Provider</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fas fa-tower-broadcast text-success me-2"></i> {{ $sms['provider'] }}</span>
                        @if(!empty($sms['connected']))
                            <span class="badge-pill success"><i class="fas fa-circle-check"></i> Connected</span>
                        @else
                            <span class="badge-pill warning"><i class="fas fa-circle-exclamation"></i> Ready for API Key</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Automated Delivery</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span class="small fw-bold text-success"><i class="fas fa-circle-check me-1"></i> Live Automated Delivery</span>
                        <span class="badge-pill success">Always Active</span>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach($sms['templates'] as $tpl)
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small">{{ $tpl['name'] }}</span>
                                <span class="badge-pill neutral" id="counter-{{ $tpl['id'] }}">{{ strlen($tpl['content']) }}/160 chars</span>
                            </div>
                            <textarea rows="3" class="f-textarea mb-2" name="templates[{{ $tpl['id'] }}]" id="text-{{ $tpl['id'] }}" oninput="updateCharCount('{{ $tpl['id'] }}', this)">{{ $tpl['content'] }}</textarea>
                            <div class="small text-muted" style="font-size: 11px;">
                                Placeholders: <code>{owner_name}</code>, <code>{pet_name}</code>, <code>{date}</code>, <code>{time}</code>, <code>{branch}</code>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>

{{-- ========================================================= --}}
{{-- TAB 5: ADMIN USERS (Super Admin ONLY) --}}
{{-- ========================================================= --}}
@if(auth()->user()->branch === null)
<div class="tab-pane" id="tab-admins">
    <div class="settings-card">
        <div class="section-head">
            <div>
                <h3 class="section-title"><i class="fas fa-user-shield text-success"></i> Clinic Staff Accounts</h3>
                <div class="section-desc">Manage authenticated personnel and assign branch authorizations.</div>
            </div>
            <button type="button" class="btn-green-save" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-user-plus"></i> Add Staff Account
            </button>
        </div>

        <div class="mb-3">
            @foreach($admins ?? $adminUsers as $admin)
                <div class="admin-item-row">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-initial">
                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark">{{ $admin->name }}</div>
                            <div class="text-muted small">{{ $admin->email }}</div>
                            <div class="small text-success mt-1"><i class="fas fa-phone me-1"></i> {{ $admin->phone_number ?: 'No phone set' }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-pill {{ $admin->branch ? 'active' : 'neutral' }}">
                            <i class="fas fa-location-dot"></i> {{ $admin->branch ?? 'System Admin' }}
                        </span>
                        <span class="badge-pill" style="background: #E8F0FE; color: #1967D2;">
                            <i class="fas fa-user-shield"></i> {{ $admin->branch ? 'Staff' : 'Admin' }}
                        </span>
                    </div>
                    <div class="text-muted small">
                        Created {{ $admin->created_at ? $admin->created_at->format('M d, Y') : 'System Seed' }}
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-soft text-success" data-bs-toggle="modal" data-bs-target="#editAdminModal-{{ $admin->id }}">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <button type="button" class="btn-soft text-primary" data-bs-toggle="modal" data-bs-target="#resetAdminModal-{{ $admin->id }}">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ========================================================= --}}
{{-- TAB 6: PREFERENCES & ACCOUNT --}}
{{-- ========================================================= --}}
<div class="tab-pane" id="tab-preferences">
    {{-- Card 1: My Admin Account --}}
    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-user-circle text-success"></i> {{ auth()->user()->branch ? 'My Staff Account' : 'My Admin Account' }}</h3>
                    <div class="section-desc">Manage your profile name, administrative email address, and station assignment.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="f-label">Full Name</label>
                    <input type="text" name="name" class="f-input @error('name') is-invalid @enderror" value="{{ old('name', auth()->user()->name) }}" required>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="f-label">Email Address</label>
                    @if(auth()->user()->branch === null)
                        <input type="email" name="email" class="f-input @error('email') is-invalid @enderror" value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    @else
                        <div class="f-input d-flex align-items-center justify-content-between bg-white" style="gap: 8px;">
                            <span class="text-truncate">{{ auth()->user()->email }}</span>
                            <span class="badge-pill neutral flex-shrink-0"><i class="fas fa-lock me-1"></i> Email is managed exclusively by Admin</span>
                        </div>
                    @endif
                </div>

                @if(auth()->user()->branch !== null)
                    <div class="col-md-6">
                        <label class="f-label">Personal Staff Mobile Number</label>
                        <input type="text" name="phone_number" class="f-input @error('phone_number') is-invalid @enderror" value="{{ old('phone_number', auth()->user()->phone_number) }}" placeholder="e.g. 09171234567">
                        <div class="f-help">Personal staff contact only. To update your branch clinic hotline for pet owners and the AI chatbot, use Clinic Information or Branches.</div>
                        @error('phone_number')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="col-md-6">
                    <label class="f-label">Branch Assignment</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fas fa-location-dot text-success me-2"></i> {{ auth()->user()->branch ?? 'All Branches' }}</span>
                        <span class="badge-pill {{ auth()->user()->branch ? 'active' : 'neutral' }}">
                            {{ auth()->user()->branch ? 'Staff' : 'Admin' }}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="f-label">System Role</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fas fa-shield-halved text-success me-2"></i> {{ auth()->user()->branch ? 'Clinic Staff' : 'System Administrator' }}</span>
                        <span class="badge-pill active"><i class="fas fa-circle-check"></i> Active Staff</span>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Details</button>
            </div>
        </div>
    </form>

    {{-- Card 2: Change Password (Super Admin ONLY) --}}
    @if(auth()->user()->branch === null)
    <form method="POST" action="{{ route('admin.profile.password') }}">
        @csrf
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-key text-success"></i> Change Password</h3>
                    <div class="section-desc">Ensure your administrative access is protected with a secure and unique password.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="f-label">Current Password</label>
                    <input type="password" name="current_password" class="f-input @error('current_password') is-invalid @enderror" placeholder="••••••••" required>
                    @error('current_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="f-label">New Password</label>
                    <input type="password" name="new_password" class="f-input @error('new_password') is-invalid @enderror" placeholder="Min. 6 characters" minlength="6" required>
                    @error('new_password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="f-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" class="f-input" placeholder="Repeat new password" minlength="6" required>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-lock"></i> Update Password</button>
            </div>
        </div>
    </form>
    @endif

    {{-- Card 3: Regional & System Preferences --}}
    <form method="POST" action="{{ route('admin.settings.preferences') }}">
        @csrf
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-gears text-success"></i> Regional &amp; System Preferences</h3>
                    <div class="section-desc">Locale standards, session timeouts, and security triggers.</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="f-label">System Timezone</label>
                    <select name="timezone" class="f-select">
                        <option value="Asia/Manila" @selected(($preferences['timezone'] ?? 'Asia/Manila') === 'Asia/Manila')>Asia/Manila (GMT+8:00)</option>
                        <option value="UTC" @selected(($preferences['timezone'] ?? 'Asia/Manila') === 'UTC')>UTC (Universal Time)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Standard Date Format</label>
                    <select name="date_format" class="f-select">
                        <option value="F j, Y" @selected(($preferences['date_format'] ?? 'F j, Y') === 'F j, Y')>October 13, 2026 (Month D, YYYY)</option>
                        <option value="m/d/Y" @selected(($preferences['date_format'] ?? 'F j, Y') === 'm/d/Y')>10/13/2026 (MM/DD/YYYY)</option>
                        <option value="d/m/Y" @selected(($preferences['date_format'] ?? 'F j, Y') === 'd/m/Y')>13/10/2026 (DD/MM/YYYY)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="f-label">Display Time Format</label>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="time_format" id="t12" value="12" @checked((string)($preferences['time_format'] ?? '12') === '12')>
                            <label class="form-check-label" for="t12">12-Hour (e.g. 02:30 PM)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="time_format" id="t24" value="24" @checked((string)($preferences['time_format'] ?? '12') === '24')>
                            <label class="form-check-label" for="t24">24-Hour (e.g. 14:30)</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="f-label">Session Inactivity Timeout</label>
                    <select name="session_timeout" class="f-select">
                        <option value="30" @selected((string)($preferences['session_timeout'] ?? '60') === '30')>30 minutes</option>
                        <option value="60" @selected((string)($preferences['session_timeout'] ?? '60') === '60')>1 hour</option>
                        <option value="240" @selected((string)($preferences['session_timeout'] ?? '60') === '240')>4 hours</option>
                        <option value="1440" @selected((string)($preferences['session_timeout'] ?? '60') === '1440')>24 hours</option>
                    </select>
                </div>
            </div>

            <hr class="my-4" style="border-color: #EAE6DE;">

            <h4 class="section-title mb-3" style="font-size: 16px;"><i class="fas fa-bell text-success"></i> Notification Channels</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="switch-wrap">
                        <div>
                            <div class="fw-bold small text-dark">Email Alerts</div>
                            <div class="text-muted" style="font-size: 11px;">Send appointment receipts via mail</div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" checked>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="danger-zone-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-bold text-danger"><i class="fas fa-triangle-exclamation me-1"></i> Maintenance Mode</div>
                        <div class="text-muted small">When active, pet owners cannot place bookings via the mobile app. Only admins can access.</div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch">
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>

@if(auth()->user()->branch === null)
{{-- MODAL: ADD BRANCH --}}
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Add Clinic Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 rounded-4" style="background: #FAF6F0; border: 1px solid rgba(196,200,188,0.4); color: #4A4E4A;">
                    <div class="fw-bold mb-1"><i class="fas fa-circle-info text-success me-1"></i> Branch Provisioning</div>
                    <div class="small mb-0">
                        Multi-branch self-service provisioning is managed via clinic onboarding. Existing branch hotlines and operating schedules can be updated directly in Settings.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-green-save" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: ADD ADMIN --}}
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Add Staff Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 rounded-4" style="background: #FAF6F0; border: 1px solid rgba(196,200,188,0.4); color: #4A4E4A;">
                    <div class="fw-bold mb-1"><i class="fas fa-user-shield text-success me-1"></i> Staff Account Provisioning</div>
                    <div class="small mb-0">
                        Multi-branch self-service provisioning is managed via clinic onboarding. Admins can edit existing staff details, station assignments, and passwords from the staff list.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-green-save" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODALS: EDIT BRANCH HOTLINE & ADDRESS --}}
@foreach($branches as $branch)
    @php
        $canEditModal = auth()->user()->branch === null ||
            ((stripos(auth()->user()->branch, 'batangas') !== false ? 'batangas' : 'mabini') === $branch['key']);
    @endphp
    @if($canEditModal)
        <div class="modal fade" id="editBranchModal-{{ $branch['key'] }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
                    <form method="POST" action="{{ route('admin.settings.branch', $branch['key']) }}">
                        @csrf
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Edit {{ $branch['name'] }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if(auth()->user()->branch === null)
                                <div class="mb-3">
                                    <label class="f-label">Branch Address</label>
                                    <input type="text" name="address" class="f-input" value="{{ $branch['address'] }}" required maxlength="255">
                                </div>
                                <div>
                                    <label class="f-label">Branch Contact Number (Hotline)</label>
                                    <div class="f-input bg-light text-muted">{{ $branch['phone'] }}</div>
                                    <div class="f-help mt-1"><i class="fas fa-lock me-1"></i> Contact number can only be updated by the assigned Branch Staff.</div>
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="f-label">Branch Contact Number (Hotline)</label>
                                    <input type="text" name="phone" class="f-input" value="{{ $branch['phone'] }}" required maxlength="30">
                                </div>
                                <div>
                                    <label class="f-label">Branch Address</label>
                                    <div class="f-input bg-light text-muted">{{ $branch['address'] }}</div>
                                    <div class="f-help mt-1"><i class="fas fa-lock me-1"></i> Address can only be modified by Admin.</div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-green-save">Save Branch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
{{-- MODALS: RESET ADMIN PASSWORD & EDIT ADMIN (Super Admin only) --}}
@if(auth()->user()->branch === null)
    @foreach($admins ?? $adminUsers as $admin)
        {{-- MODAL: EDIT ADMIN DETAILS --}}
        <div class="modal fade" id="editAdminModal-{{ $admin->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
                    <form method="POST" action="{{ route('admin.users.update', $admin->id) }}">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Edit Admin Details</h5>
                                <p class="text-muted small mb-0">Update station, phone, and profile for <strong>{{ $admin->name }}</strong></p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="mb-3">
                                <label class="f-label">Full Name</label>
                                <input type="text" name="name" class="f-input" value="{{ $admin->name }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Email Address</label>
                                <input type="email" name="email" class="f-input" value="{{ $admin->email }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Personal Mobile Number</label>
                                <input type="text" name="phone_number" class="f-input" value="{{ $admin->phone_number }}" placeholder="e.g. 09171234567">
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Branch Station Assignment</label>
                                <select name="branch" class="f-select">
                                    <option value="" {{ !$admin->branch ? 'selected' : '' }}>System Admin (Account Management)</option>
                                    <option value="Mabini Branch" {{ $admin->branch === 'Mabini Branch' ? 'selected' : '' }}>Mabini Branch</option>
                                    <option value="Batangas City Branch" {{ $admin->branch === 'Batangas City Branch' ? 'selected' : '' }}>Batangas City Branch</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="resetAdminModal-{{ $admin->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
                    <form method="POST" action="{{ route('admin.users.reset-password', $admin->id) }}">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Reset Admin Password</h5>
                                <p class="text-muted small mb-0">Set a new password for <strong>{{ $admin->name }}</strong> ({{ $admin->email }})</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="mb-3">
                                <label class="f-label">New Password</label>
                                <input type="password" name="new_password" class="f-input" placeholder="Minimum 6 characters" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation" class="f-input" placeholder="Repeat new password" minlength="6" required>
                            </div>
                            <div class="p-3 rounded-4 small" style="background: #FAF6F0; border: 1px solid rgba(196,200,188,0.4); color: #6B6358;">
                                <i class="fas fa-circle-info text-success me-1"></i> The administrator will be able to log in immediately using this new password.
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-green-save"><i class="fas fa-key"></i> Set New Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

@if(auth()->user()->branch === null)
    <!-- Modal: Interactive 1:1 Clinic Logo Cropper -->
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,0.18); overflow: hidden;">
                <div class="modal-header border-0 pb-0" style="padding: 24px 28px 12px;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #C8E8D0; color: #4A7C59;">
                            <i class="fas fa-crop-simple fs-6"></i>
                        </span>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="cropperModalLabel" style="font-family: 'Literata', serif; color: #2E3230;">Crop Clinic Logo</h5>
                            <p class="text-muted small mb-0">Adjust position, zoom, and orientation for a clean 1:1 circular avatar.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 20px 28px;">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-8">
                            <div style="width: 100%; height: 360px; background-color: #1A1A1A; border-radius: 16px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <img id="cropperImageSrc" src="" alt="To Crop" style="max-width: 100%; max-height: 100%; display: block;">
                            </div>
                            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropZoomInBtn">
                                    <i class="fas fa-magnifying-glass-plus me-1"></i> Zoom In
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropZoomOutBtn">
                                    <i class="fas fa-magnifying-glass-minus me-1"></i> Zoom Out
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropRotateLeftBtn">
                                    <i class="fas fa-rotate-left me-1"></i> Rotate
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropResetBtn">
                                    <i class="fas fa-arrow-rotate-right me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 bg-light rounded-4 border">
                                <div class="small fw-bold text-uppercase text-muted mb-2">Circle Preview</div>
                                <div id="cropPreviewContainer" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; margin: 0 auto 12px; border: 3px solid #4A7C59; box-shadow: 0 4px 14px rgba(74,124,89,0.25); background: #FAF6F0;">
                                </div>
                                <div class="small text-muted" style="font-size: 11px;">
                                    This circular avatar will display across the Admin Sidebar, Mobile Home header, and Mobile About sheet.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0" style="padding: 12px 28px 24px;">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-green-save px-4" id="applyCropBtn">
                        <i class="fas fa-check me-1"></i> Crop &amp; Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if(auth()->user()->branch !== null)
    <!-- Modal: iOS-Style Scroll-Wheel Time Picker -->
    <div class="modal fade" id="wheelTimePickerModal" tabindex="-1" aria-labelledby="wheelPickerTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 340px;">
            <div class="modal-content" style="background: #FFFFFF; border-radius: 20px; border: none; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.16); overflow: hidden;">
                <div class="modal-header border-0 pb-1 pt-3 px-4">
                    <h6 class="modal-title mb-0" id="wheelPickerTitle" style="font-family: 'Literata', serif; font-weight: 700; font-size: 16px; color: #2E3230;">
                        Select Time
                    </h6>
                </div>
                <div class="modal-body px-3 py-2">
                    <div class="wheel-drum-wrap">
                        <div class="wheel-highlight-bar"></div>
                        <div class="wheel-fade-top"></div>
                        <div class="wheel-fade-bottom"></div>
                        <div class="wheel-col" id="wheelHours" role="listbox" aria-label="Hour"></div>
                        <div class="wheel-col" id="wheelMinutes" role="listbox" aria-label="Minute"></div>
                        <div class="wheel-col" id="wheelPeriods" role="listbox" aria-label="AM or PM"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-1 pb-3 px-4 justify-content-end gap-2">
                    <button type="button" class="wheel-action-btn cancel" data-bs-dismiss="modal">CANCEL</button>
                    <button type="button" class="wheel-action-btn ok" id="wheelPickerOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Vanilla JS Tab Switching with Hash & Validation Error Support
        const tabButtons = document.querySelectorAll('.settings-tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        function activateTab(targetTab) {
            tabButtons.forEach(btn => {
                if (btn.getAttribute('data-tab') === targetTab) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            tabPanes.forEach(pane => {
                if (pane.id === 'tab-' + targetTab) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
        }

        tabButtons.forEach(button => {
            button.addEventListener('click', function () {
                const targetTab = this.getAttribute('data-tab');
                activateTab(targetTab);
                if (history.pushState) {
                    history.pushState(null, null, '#' + targetTab);
                } else {
                    window.location.hash = targetTab;
                }
            });
        });

        // Check URL hash on page load
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById('tab-' + hash)) {
            activateTab(hash);
        }
        @if($errors->has('current_password') || $errors->has('new_password') || $errors->has('name') || $errors->has('email'))
            activateTab('preferences');
        @endif
        @if($errors->has('templates') || $errors->has('templates.*'))
            activateTab('sms');
        @endif
    });

    // SMS Character counter
    function updateCharCount(id, textarea) {
        const counter = document.getElementById('counter-' + id);
        if (counter) {
            counter.innerText = textarea.value.length + '/160 chars';
        }
    }

    // Dynamic FAQ adding
    function addNewFaq() {
        const container = document.getElementById('faqContainer');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'faq-box';
        div.id = 'faq-' + count;
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge-pill neutral">FAQ #${count}</span>
                <button type="button" class="btn btn-sm text-danger p-0" onclick="this.closest('.faq-box').remove()">
                    <i class="fas fa-trash-can"></i>
                </button>
            </div>
            <input type="text" class="f-input mb-2" placeholder="Question">
            <textarea rows="2" class="f-textarea" placeholder="Answer"></textarea>
        `;
        container.appendChild(div);
    }
</script>
@if(auth()->user()->branch === null)
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let cropper = null;
        const logoFileInput = document.getElementById('logoFileInput');
        const triggerLogoPickerBtn = document.getElementById('triggerLogoPickerBtn');
        const cropperModalEl = document.getElementById('cropperModal');
        let cropperModalInstance = null;
        const cropperImageSrc = document.getElementById('cropperImageSrc');
        const applyCropBtn = document.getElementById('applyCropBtn');
        const croppedLogoInput = document.getElementById('croppedLogoInput');

        if (triggerLogoPickerBtn && logoFileInput && cropperModalEl) {
            triggerLogoPickerBtn.addEventListener('click', function () {
                logoFileInput.value = '';
                logoFileInput.click();
            });

            logoFileInput.addEventListener('change', function (e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    if (!file.type.match(/^image\//)) {
                        alert('Please select a valid image file (PNG, JPG, WEBP).');
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        alert('Image size exceeds 10MB. Please choose a smaller image.');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        cropperImageSrc.src = evt.target.result;

                        if (!cropperModalInstance) {
                            cropperModalInstance = new bootstrap.Modal(cropperModalEl, {
                                backdrop: 'static',
                                keyboard: false
                            });
                        }
                        cropperModalInstance.show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            cropperModalEl.addEventListener('shown.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                }
                if (typeof Cropper !== 'undefined') {
                    cropper = new Cropper(cropperImageSrc, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.95,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        preview: '#cropPreviewContainer'
                    });
                }
            });

            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropperImageSrc.src = '';
            });

            document.getElementById('cropZoomInBtn')?.addEventListener('click', function () {
                cropper?.zoom(0.1);
            });
            document.getElementById('cropZoomOutBtn')?.addEventListener('click', function () {
                cropper?.zoom(-0.1);
            });
            document.getElementById('cropRotateLeftBtn')?.addEventListener('click', function () {
                cropper?.rotate(-90);
            });
            document.getElementById('cropResetBtn')?.addEventListener('click', function () {
                cropper?.reset();
            });

            applyCropBtn?.addEventListener('click', function () {
                if (!cropper) return;

                const canvas = cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                if (!canvas) {
                    alert('Could not process cropped image.');
                    return;
                }

                const croppedDataUrl = canvas.toDataURL('image/png');
                if (croppedLogoInput) {
                    croppedLogoInput.value = croppedDataUrl;
                }

                const previewImg = document.getElementById('clinicLogoPreviewImg');
                if (previewImg) {
                    previewImg.src = croppedDataUrl;
                    previewImg.style.display = 'block';
                }
                const pawIcon = document.getElementById('clinicLogoPawIcon');
                if (pawIcon) {
                    pawIcon.style.display = 'none';
                }

                const statusText = document.getElementById('logoStatusText');
                if (statusText) {
                    statusText.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Cropped logo ready! Click "Save Changes" below.</span>';
                }

                if (cropperModalInstance) {
                    cropperModalInstance.hide();
                }
            });
        }
    });
</script>
@endif
@if(auth()->user()->branch !== null)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ITEM_HEIGHT = 44;
        const HOURS = Array.from({ length: 12 }, (_, i) => String(i + 1));
        const MINUTES = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));
        const PERIODS = ['AM', 'PM'];

        const modalEl = document.getElementById('wheelTimePickerModal');
        const titleEl = document.getElementById('wheelPickerTitle');
        const hoursCol = document.getElementById('wheelHours');
        const minutesCol = document.getElementById('wheelMinutes');
        const periodsCol = document.getElementById('wheelPeriods');
        const okBtn = document.getElementById('wheelPickerOkBtn');
        const clinicForm = document.getElementById('clinicProfileForm');

        if (!modalEl || !hoursCol || !minutesCol || !periodsCol || !okBtn) {
            return;
        }

        let activeTarget = null;
        let pendingParsedTime = { hour: 9, minute: 0, period: 'AM' };

        function applyDrumPerspective(col) {
            const centerIdx = col.scrollTop / ITEM_HEIGHT;
            const items = col.children;
            for (let i = 0; i < items.length; i++) {
                const dist = Math.abs(i - centerIdx);
                const el = items[i];
                if (dist < 0.45) {
                    el.style.color = '#111827';
                    el.style.fontWeight = '600';
                    el.style.fontSize = '22px';
                    el.style.opacity = '1';
                    el.style.transform = 'scale(1)';
                } else {
                    const opacity = Math.max(0.18, 1 - dist * 0.28);
                    const scale = Math.max(0.76, 1 - dist * 0.08);
                    const fontSize = dist < 1.4 ? '20px' : '18px';
                    el.style.color = '#6B7280';
                    el.style.fontWeight = '400';
                    el.style.fontSize = fontSize;
                    el.style.opacity = opacity.toFixed(2);
                    el.style.transform = `scale(${scale.toFixed(3)})`;
                }
            }
        }

        function populateWheel(col, values) {
            col.innerHTML = '';
            values.forEach((val, idx) => {
                const div = document.createElement('div');
                div.className = 'wheel-item';
                div.textContent = val;
                div.setAttribute('role', 'option');
                div.addEventListener('click', function () {
                    col.scrollTo({ top: idx * ITEM_HEIGHT, behavior: 'smooth' });
                });
                col.appendChild(div);
            });

            let rafId = null;
            col.addEventListener('scroll', function () {
                if (rafId) return;
                rafId = window.requestAnimationFrame(function () {
                    rafId = null;
                    applyDrumPerspective(col);
                });
            }, { passive: true });
        }

        populateWheel(hoursCol, HOURS);
        populateWheel(minutesCol, MINUTES);
        populateWheel(periodsCol, PERIODS);

        function parseTime12h(str) {
            const match = String(str || '').trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
            if (!match) {
                return { hour: 9, minute: 0, period: 'AM' };
            }
            const h = Math.min(12, Math.max(1, parseInt(match[1], 10) || 9));
            const m = Math.min(59, Math.max(0, parseInt(match[2], 10) || 0));
            const p = match[3].toUpperCase() === 'PM' ? 'PM' : 'AM';
            return { hour: h, minute: m, period: p };
        }

        function formatTime12h(hour, minute, period) {
            return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')} ${period}`;
        }

        function timeToMinutes(str) {
            const parsed = parseTime12h(str);
            let h24 = parsed.hour % 12;
            if (parsed.period === 'PM') h24 += 12;
            return h24 * 60 + parsed.minute;
        }

        function setWheelToIndex(col, idx, length) {
            const clamped = Math.min(length - 1, Math.max(0, idx));
            col.scrollTop = clamped * ITEM_HEIGHT;
            applyDrumPerspective(col);
        }

        function readWheelIndex(col, length) {
            const idx = Math.round(col.scrollTop / ITEM_HEIGHT);
            return Math.min(length - 1, Math.max(0, idx));
        }

        function syncScheduleCard(key) {
            const closedSwitch = document.getElementById(`scheduleClosed-${key}`);
            const openBtn = document.getElementById(`scheduleBtn-${key}-open`);
            const closeBtn = document.getElementById(`scheduleBtn-${key}-close`);
            const hiddenInput = document.getElementById(`scheduleInput-${key}`);
            const cardEl = document.getElementById(`scheduleCard-${key}`);
            const closedPill = document.getElementById(`scheduleClosedPill-${key}`);
            const errorEl = document.getElementById(`scheduleError-${key}`);

            if (!openBtn || !closeBtn || !hiddenInput || !cardEl) return true;

            const isClosed = Boolean(closedSwitch && closedSwitch.checked);
            openBtn.disabled = isClosed;
            closeBtn.disabled = isClosed;

            if (isClosed) {
                cardEl.classList.add('is-closed');
                cardEl.classList.remove('has-error');
                if (closedPill) closedPill.classList.remove('d-none');
                if (errorEl) errorEl.classList.add('d-none');
                hiddenInput.value = 'Closed';
                return true;
            }

            cardEl.classList.remove('is-closed');
            if (closedPill) closedPill.classList.add('d-none');

            const openVal = openBtn.getAttribute('data-time') || '09:00 AM';
            const closeVal = closeBtn.getAttribute('data-time') || '06:00 PM';
            hiddenInput.value = `${openVal} - ${closeVal}`;

            const validRange = timeToMinutes(closeVal) > timeToMinutes(openVal);
            if (!validRange) {
                cardEl.classList.add('has-error');
                if (errorEl) errorEl.classList.remove('d-none');
            } else {
                cardEl.classList.remove('has-error');
                if (errorEl) errorEl.classList.add('d-none');
            }
            return validRange;
        }

        document.querySelectorAll('.schedule-time-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.disabled) return;
                activeTarget = {
                    key: this.getAttribute('data-schedule-key'),
                    bound: this.getAttribute('data-schedule-bound'),
                    btn: this,
                };
                pendingParsedTime = parseTime12h(this.getAttribute('data-time'));
                if (titleEl) {
                    titleEl.textContent = this.getAttribute('data-schedule-label') || 'Select Time';
                }
                if (window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        });

        modalEl.addEventListener('shown.bs.modal', function () {
            setWheelToIndex(hoursCol, pendingParsedTime.hour - 1, HOURS.length);
            setWheelToIndex(minutesCol, pendingParsedTime.minute, MINUTES.length);
            setWheelToIndex(periodsCol, pendingParsedTime.period === 'PM' ? 1 : 0, PERIODS.length);
        });

        okBtn.addEventListener('click', function () {
            if (!activeTarget || !activeTarget.btn) return;
            const hourIdx = readWheelIndex(hoursCol, HOURS.length);
            const minuteIdx = readWheelIndex(minutesCol, MINUTES.length);
            const periodIdx = readWheelIndex(periodsCol, PERIODS.length);

            const formatted = formatTime12h(
                parseInt(HOURS[hourIdx], 10),
                parseInt(MINUTES[minuteIdx], 10),
                PERIODS[periodIdx]
            );

            activeTarget.btn.setAttribute('data-time', formatted);
            const valSpan = activeTarget.btn.querySelector('.schedule-time-value');
            if (valSpan) {
                valSpan.textContent = formatted;
            }

            syncScheduleCard(activeTarget.key);

            if (window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        });

        document.querySelectorAll('.schedule-closed-switch').forEach(sw => {
            sw.addEventListener('change', function () {
                syncScheduleCard(this.getAttribute('data-schedule-key'));
            });
        });

        if (clinicForm) {
            clinicForm.addEventListener('submit', function (e) {
                const keys = ['weekday', 'saturday', 'sunday'];
                let allValid = true;
                let firstInvalidCard = null;
                keys.forEach(k => {
                    const ok = syncScheduleCard(k);
                    if (!ok) {
                        allValid = false;
                        if (!firstInvalidCard) {
                            firstInvalidCard = document.getElementById(`scheduleCard-${k}`);
                        }
                    }
                });
                if (!allValid) {
                    e.preventDefault();
                    if (firstInvalidCard) {
                        firstInvalidCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
    });
</script>
@endif
@endsection
