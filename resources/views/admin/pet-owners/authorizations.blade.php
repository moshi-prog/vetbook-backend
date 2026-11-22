@extends('admin.layouts.app')

@section('title', 'Account Authorizations')

@section('content')
<style>
    .auth-header-title {
        font-family: 'Literata', Georgia, serif;
        font-weight: 700;
        color: #1A1D1A;
        font-size: 28px;
        margin-bottom: 4px;
    }
    .auth-stat-card {
        background: #FFFFFF;
        border: 1px solid #EAE6DE;
        border-radius: 20px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }
    .auth-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .auth-filter-bar {
        background: #FFFFFF;
        border: 1px solid #EAE6DE;
        border-radius: 20px;
        padding: 14px 18px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }
    .auth-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        color: #5C5F5C;
        background: #F5F1EA;
        transition: all 0.15s ease;
    }
    .auth-tab:hover {
        background: #EAE4D8;
        color: #1A1D1A;
    }
    .auth-tab.active {
        background: #4A7C59;
        color: #FFFFFF;
    }
    .applicant-card {
        background: #FFFFFF;
        border: 1px solid #EAE6DE;
        border-radius: 24px;
        padding: 20px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 16px rgba(46, 50, 48, 0.04);
    }
    .applicant-status-pill {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 4px 10px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .applicant-status-pill.pending {
        background: #FEF4E5;
        color: #9A5B00;
    }
    .applicant-status-pill.approved {
        background: #E8F5E9;
        color: #2A6038;
    }
    .applicant-status-pill.rejected {
        background: #FFDAD8;
        color: #93000A;
    }
    .id-type-badge {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .id-preview-box {
        position: relative;
        height: 220px;
        background: #F5F1EA;
        border: 1.5px dashed #D6CFC2;
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .id-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.2s ease;
    }
    .id-preview-box:hover img {
        transform: scale(1.02);
    }
    .id-zoom-chip {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(26, 29, 26, 0.78);
        color: #FFFFFF;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 9999px;
        backdrop-filter: blur(4px);
    }
    .id-missing-box {
        height: 220px;
        background: #FAF8F4;
        border: 1.5px dashed #E0DAD0;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #797D79;
        padding: 20px;
    }
    .lightbox-stage {
        background: #1A1D1A;
        border-radius: 16px;
        min-height: 360px;
        max-height: 70vh;
        overflow: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .lightbox-stage img {
        max-width: 100%;
        max-height: 64vh;
        object-fit: contain;
        transition: transform 0.2s ease;
        transform-origin: center center;
    }
</style>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="auth-header-title">Account Authorizations</h1>
        <p class="mb-0 text-muted" style="font-size: 14px;">
            Inspect each verified applicant's submitted photo or valid ID before authorizing VetBook app access.
        </p>
    </div>
    <a href="{{ route('admin.pet-owners.index') }}"
       class="btn rounded-pill px-4 py-2"
       style="background: #F5F1EA; color: #2E3230; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; border: 1px solid #E2DCD0;">
        <i class="fa-solid fa-users me-1"></i> Pet Owners Directory
    </a>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="auth-stat-card">
            <div class="auth-stat-icon" style="background: #FEF4E5; color: #D99B26;">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div style="font-size: 24px; font-weight: 800; color: #1A1D1A; line-height: 1.1;">{{ $stats['pending'] }}</div>
                <div style="font-size: 12px; color: #6B6358; font-weight: 700;">Pending Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="auth-stat-card">
            <div class="auth-stat-icon" style="background: #EBF3ED; color: #4A7C59;">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <div>
                <div style="font-size: 24px; font-weight: 800; color: #1A1D1A; line-height: 1.1;">{{ $stats['with_id'] }}</div>
                <div style="font-size: 12px; color: #6B6358; font-weight: 700;">With Photo / ID</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="auth-stat-card">
            <div class="auth-stat-icon" style="background: #E8F5E9; color: #2A6038;">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <div style="font-size: 24px; font-weight: 800; color: #1A1D1A; line-height: 1.1;">{{ $stats['approved_today'] }}</div>
                <div style="font-size: 12px; color: #6B6358; font-weight: 700;">Approved Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="auth-stat-card">
            <div class="auth-stat-icon" style="background: #FFDAD8; color: #BA1A1A;">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <div style="font-size: 24px; font-weight: 800; color: #1A1D1A; line-height: 1.1;">{{ $stats['rejected'] }}</div>
                <div style="font-size: 12px; color: #6B6358; font-weight: 700;">Declined / Rejected</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Tabs & Search --}}
<div class="auth-filter-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('admin.account-authorizations.index', array_filter(['filter' => 'pending', 'search' => $search])) }}"
           class="auth-tab {{ $filter === 'pending' ? 'active' : '' }}">
            <span>Pending Review</span>
            @if($stats['pending'] > 0)
                <span class="badge rounded-pill" style="background: {{ $filter === 'pending' ? '#FFFFFF' : '#D99B26' }}; color: {{ $filter === 'pending' ? '#4A7C59' : '#FFFFFF' }}; font-size: 11px;">
                    {{ $stats['pending'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('admin.account-authorizations.index', array_filter(['filter' => 'rejected', 'search' => $search])) }}"
           class="auth-tab {{ $filter === 'rejected' ? 'active' : '' }}">
            <span>Declined</span>
            @if($stats['rejected'] > 0)
                <span class="badge rounded-pill" style="background: {{ $filter === 'rejected' ? '#FFFFFF' : '#BA1A1A' }}; color: {{ $filter === 'rejected' ? '#4A7C59' : '#FFFFFF' }}; font-size: 11px;">
                    {{ $stats['rejected'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('admin.account-authorizations.index', array_filter(['filter' => 'approved', 'search' => $search])) }}"
           class="auth-tab {{ $filter === 'approved' ? 'active' : '' }}">
            <span>Approved</span>
        </a>
        <a href="{{ route('admin.account-authorizations.index', array_filter(['filter' => 'all', 'search' => $search])) }}"
           class="auth-tab {{ $filter === 'all' ? 'active' : '' }}">
            <span>All Verified</span>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.account-authorizations.index') }}" class="d-flex align-items-center gap-2">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div class="input-group input-group-sm" style="min-width: 240px;">
            <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3" style="border-color: #DCD6CA;">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
            </span>
            <input type="text" name="search" value="{{ $search }}"
                   class="form-control border-start-0 rounded-end-pill pe-3"
                   style="border-color: #DCD6CA; font-size: 13px;"
                   placeholder="Search name, email, or phone...">
        </div>
        <button type="submit" class="btn btn-sm rounded-pill px-3" style="background: #4A7C59; color: #FFFFFF; font-weight: 700;">
            Search
        </button>
        @if($search !== '')
            <a href="{{ route('admin.account-authorizations.index', ['filter' => $filter]) }}"
               class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                Clear
            </a>
        @endif
    </form>
</div>

{{-- Applicant Cards Grid --}}
@if($applicants->isEmpty())
    <div class="text-center py-5 px-4 bg-white rounded-4 border" style="border-color: #EAE6DE !important;">
        <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle"
             style="width: 68px; height: 68px; background: #EBF3ED; color: #4A7C59; font-size: 28px;">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h4 style="font-family: 'Literata', serif; font-weight: 700; color: #1A1D1A;">
            @if($filter === 'pending')
                All caught up — no pending account authorizations
            @else
                No verified applicants found
            @endif
        </h4>
        <p class="text-muted mb-0" style="font-size: 14px; max-width: 440px; margin: 0 auto;">
            @if($search !== '')
                No verified accounts match "{{ $search }}" in this tab. Try clearing your search filter.
            @else
                New pet owner registrations only appear here after they verify their 6-digit email OTP code.
            @endif
        </p>
    </div>
@else
    <div class="row g-4">
        @foreach($applicants as $applicant)
            @php
                $statusVal = $applicant->approval_status ?? 'approved';
                $idTypeLabel = match($applicant->verification_id_type) {
                    'selfie', 'selfie_with_id' => 'Clear Selfie',
                    'valid_id' => 'Valid ID',
                    default => $applicant->verification_id_image ? 'Verification Photo' : null,
                };
            @endphp
            <div class="col-12 col-xl-6">
                <div class="applicant-card">
                    <div>
                        {{-- Applicant Header --}}
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div class="d-flex align-items-center gap-3">
                                @include('admin.pet-owners._avatar', ['owner' => $applicant, 'size' => 52])
                                <div>
                                    <a href="{{ route('admin.pet-owners.show', $applicant->id) }}"
                                       class="text-decoration-none"
                                       style="font-family: 'Literata', serif; font-size: 17px; font-weight: 700; color: #1A1D1A;">
                                        {{ $applicant->name }}
                                    </a>
                                    <div class="text-muted" style="font-size: 13px;">
                                        <i class="fa-regular fa-envelope me-1"></i>{{ $applicant->email }}
                                    </div>
                                    <div class="text-muted" style="font-size: 13px;">
                                        <i class="fa-solid fa-phone me-1"></i>{{ $applicant->phone_number ?? 'No phone' }}
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column align-items-end gap-1">
                                @if($statusVal === 'pending')
                                    <span class="applicant-status-pill pending">
                                        <i class="fa-solid fa-clock"></i> Pending Review
                                    </span>
                                @elseif($statusVal === 'rejected')
                                    <span class="applicant-status-pill rejected">
                                        <i class="fa-solid fa-ban"></i> Declined
                                    </span>
                                @else
                                    <span class="applicant-status-pill approved">
                                        <i class="fa-solid fa-circle-check"></i> Authorized
                                    </span>
                                @endif

                                @if($idTypeLabel)
                                    <span class="id-type-badge">
                                        <i class="fa-solid {{ str_contains(strtolower($idTypeLabel), 'selfie') ? 'fa-camera-retro' : 'fa-id-card' }}"></i>
                                        {{ $idTypeLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Submitted Photo / Valid ID Preview --}}
                        @if(!empty($applicant->verification_id_image))
                            <div class="id-preview-box mb-3"
                                 role="button"
                                 tabindex="0"
                                 data-src="{{ $applicant->verification_id_image }}"
                                 data-name="{{ $applicant->name }}"
                                 data-email="{{ $applicant->email }}"
                                 data-type="{{ $idTypeLabel ?? 'Verification Photo' }}"
                                 onclick="openIdLightbox(this)"
                                 onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openIdLightbox(this);}">
                                <img src="{{ $applicant->verification_id_image }}"
                                     alt="Verification ID for {{ $applicant->name }}"
                                     loading="lazy">
                                <span class="id-zoom-chip">
                                    <i class="fa-solid fa-magnifying-glass-plus me-1"></i> Click to Zoom &amp; Inspect
                                </span>
                            </div>
                        @else
                            <div class="id-missing-box mb-3">
                                <i class="fa-regular fa-id-card fa-2x mb-2" style="color: #B4AC9E;"></i>
                                <div class="fw-bold" style="font-size: 13px; color: #5C5F5C;">No verification photo or ID attached</div>
                                <div style="font-size: 12px;">Legacy or pre-requirement registration</div>
                            </div>
                        @endif
                    </div>

                    {{-- Metadata & Actions --}}
                    <div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom" style="border-color: #EFEBE2 !important; font-size: 12px; color: #6B6358;">
                            <span>
                                <i class="fa-solid fa-envelope-circle-check text-success me-1"></i>
                                Email verified {{ $applicant->email_verified_at ? $applicant->email_verified_at->diffForHumans() : '' }}
                            </span>
                            <span>
                                Registered {{ $applicant->created_at ? $applicant->created_at->format('M d, Y · g:i A') : '' }}
                            </span>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('admin.pet-owners.show', $applicant->id) }}"
                                   class="btn btn-sm rounded-pill px-3"
                                   style="background: #F5F1EA; color: #2E3230; font-weight: 700; font-size: 12px;">
                                    <i class="fa-regular fa-user me-1"></i> Profile
                                </a>
                                @if(!empty($applicant->verification_id_image))
                                    <button type="button"
                                            class="btn btn-sm rounded-pill px-3"
                                            style="background: #EBF3ED; color: #2A6038; font-weight: 700; font-size: 12px;"
                                            data-src="{{ $applicant->verification_id_image }}"
                                            data-name="{{ $applicant->name }}"
                                            data-email="{{ $applicant->email }}"
                                            data-type="{{ $idTypeLabel ?? 'Verification Photo' }}"
                                            onclick="openIdLightbox(this)">
                                        <i class="fa-solid fa-magnifying-glass-plus me-1"></i> Zoom ID
                                    </button>
                                @endif
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                @if($statusVal !== 'rejected')
                                    <form method="POST" action="{{ route('admin.pet-owners.reject', $applicant->id) }}" class="m-0">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm rounded-pill px-3 border-0"
                                                style="background: #FFDAD8; color: #690005; font-weight: 700; font-size: 12px;">
                                            <i class="fa-solid fa-xmark me-1"></i> Decline
                                        </button>
                                    </form>
                                @endif
                                @if($statusVal !== 'approved')
                                    <form method="POST" action="{{ route('admin.pet-owners.approve', $applicant->id) }}" class="m-0">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm rounded-pill px-3 border-0"
                                                style="background: #4A7C59; color: #FFFFFF; font-weight: 700; font-size: 12px;">
                                            <i class="fa-solid fa-check me-1"></i> Authorize Account
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($applicants->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $applicants->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endif

{{-- Zoomable ID Lightbox Modal --}}
<div class="modal fade" id="idLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden;">
            <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title mb-0" id="lightboxOwnerName" style="font-family: 'Literata', serif; font-weight: 700; color: #1A1D1A;">
                            Verification Photo
                        </h5>
                        <span id="lightboxIdType" class="id-type-badge"></span>
                    </div>
                    <div id="lightboxOwnerEmail" class="text-muted" style="font-size: 12px;"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <span class="text-muted" style="font-size: 12px;">
                        <i class="fa-solid fa-shield-halved me-1 text-success"></i> Use controls to zoom in or rotate if needed
                    </span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" onclick="adjustLightboxZoom(-0.25)" title="Zoom Out">
                            <i class="fa-solid fa-magnifying-glass-minus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="lightboxZoomReset" onclick="resetLightboxTransform()" title="Reset View">
                            100%
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="adjustLightboxZoom(0.25)" title="Zoom In">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="rotateLightboxImage()" title="Rotate 90°">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </div>
                <div class="lightbox-stage">
                    <img id="lightboxImg" src="" alt="Submitted Verification Photo">
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let lightboxScale = 1;
    let lightboxRotation = 0;

    function applyLightboxTransform() {
        const img = document.getElementById('lightboxImg');
        const resetBtn = document.getElementById('lightboxZoomReset');
        if (img) {
            img.style.transform = `scale(${lightboxScale}) rotate(${lightboxRotation}deg)`;
        }
        if (resetBtn) {
            resetBtn.textContent = Math.round(lightboxScale * 100) + '%';
        }
    }

    window.resetLightboxTransform = function () {
        lightboxScale = 1;
        lightboxRotation = 0;
        applyLightboxTransform();
    };

    window.adjustLightboxZoom = function (delta) {
        lightboxScale = Math.min(3, Math.max(0.5, Number((lightboxScale + delta).toFixed(2))));
        applyLightboxTransform();
    };

    window.rotateLightboxImage = function () {
        lightboxRotation = (lightboxRotation + 90) % 360;
        applyLightboxTransform();
    };

    window.openIdLightbox = function (el) {
        const src = el.getAttribute('data-src');
        if (!src) return;

        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightboxOwnerName').textContent = el.getAttribute('data-name') || 'Verification Photo';
        document.getElementById('lightboxOwnerEmail').textContent = el.getAttribute('data-email') || '';
        document.getElementById('lightboxIdType').textContent = el.getAttribute('data-type') || 'Valid ID';

        window.resetLightboxTransform();

        const modalEl = document.getElementById('idLightboxModal');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };
</script>
@endsection
