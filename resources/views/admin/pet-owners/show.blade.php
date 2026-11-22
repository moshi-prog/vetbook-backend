@extends('admin.layouts.app')

@section('title', $owner->name)

@section('styles')
<style>
    .po-subtitle { font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358; margin: 0; }
    .back-link {
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #4A7C59; text-decoration: none;
        margin-bottom: 16px; display: inline-block;
    }
    .owner-avatar {
        border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Literata', serif; font-weight: 700; flex-shrink: 0; position: relative; overflow: hidden;
    }
    .owner-avatar img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .pet-avatar {
        position: relative; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Literata', serif; font-weight: 700; flex-shrink: 0; overflow: hidden;
    }
    .pet-avatar img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }

    .banner {
        background: #F5F1EA; border-radius: 24px; padding: 28px; margin-bottom: 24px;
        display: flex; gap: 24px; align-items: center; flex-wrap: wrap;
    }
    .banner h2 { font-family: 'Literata', serif; font-size: 28px; font-weight: 700; color: #2E3230; margin: 0 0 6px; }
    .role-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 14px; border-radius: 9999px;
        background: #C8E8D0; color: #2A6038; font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700;
    }
    .contact-link {
        display: inline-flex; align-items: center; gap: 8px; font-family: 'Nunito Sans', sans-serif; font-size: 14px;
        color: #2E3230; text-decoration: none; margin-right: 18px; margin-top: 8px;
    }
    .contact-link i { color: #4A7C59; }
    .contact-link:hover { color: #4A7C59; }

    .stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); display: flex; align-items: center; gap: 16px;
    }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center;
        justify-content: center; font-size: 20px; flex-shrink: 0;
    }
    .stat-value { font-family: 'Literata', serif; font-size: 26px; font-weight: 700; color: #2E3230; line-height: 1; }
    .stat-label {
        font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #6B6358;
        text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;
    }
    @media (max-width: 991px) { .stat-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .stat-row { grid-template-columns: 1fr; } }

    .section-card {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); margin-bottom: 24px;
    }
    .section-card h3 {
        font-family: 'Literata', serif; font-size: 18px; font-weight: 700; color: #2E3230; margin: 0 0 16px;
        padding-bottom: 12px; border-bottom: 1px solid #EAE6DE; display: flex; justify-content: space-between;
        align-items: center; gap: 12px;
    }
    .section-card h3 a {
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; color: #4A7C59; text-decoration: none;
    }

    .pet-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    @media (max-width: 575px) { .pet-grid { grid-template-columns: 1fr; } }
    .pet-tile {
        display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 16px; background: #FAF6F0;
        text-decoration: none; transition: background 0.15s; border: 1px solid transparent;
    }
    .pet-tile:hover { background: #F0ECE4; border-color: #EAE6DE; }
    .pet-tile .nm { font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 14px; color: #2E3230; }
    .pet-tile .sub { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; }

    .appt-table { width: 100%; font-family: 'Nunito Sans', sans-serif; font-size: 13px; margin: 0; }
    .appt-table th {
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #6B6358;
        padding: 12px 14px; background: #F5F1EA; border-bottom: 1px solid #EAE6DE; white-space: nowrap;
    }
    .appt-table td { padding: 14px; border-bottom: 1px solid #EAE6DE; color: #2E3230; vertical-align: middle; }
    .appt-table tbody tr { cursor: pointer; transition: background 0.15s; }
    .appt-table tbody tr:hover { background: rgba(245,241,234,0.6); }
    .appt-table tbody tr:last-child td { border-bottom: none; }
    .status-pill { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    .status-pill.pending { background: #F8E0A8; color: #554020; }
    .status-pill.approved { background: #C8E8D0; color: #2A6038; }
    .status-pill.completed { background: #EAE6DE; color: #4A4E4A; }
    .status-pill.cancelled { background: #FFDAD8; color: #690005; }
    .branch-chip {
        display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px;
        background: #F0ECE4; color: #4A4E4A; font-family: 'Nunito Sans', sans-serif; font-size: 11px; font-weight: 600;
        margin: 2px 4px 2px 0;
    }
    .branch-chip.mine { background: #C8E8D0; color: #2A6038; }

    .action-btn-lg {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 16px;
        border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-size: 14px; font-weight: 600;
        border: none; text-decoration: none; transition: all 0.15s; margin-bottom: 10px;
    }
    .action-btn-lg.primary { background: #4A7C59; color: #FFFFFF; }
    .action-btn-lg.primary:hover { background: #3d6b4a; color: #FFFFFF; }
    .action-btn-lg.secondary { background: #F5F1EA; color: #4A4E4A; }
    .action-btn-lg.secondary:hover { background: #EAE6DE; color: #2E3230; }
    .action-btn-lg.disabled { opacity: 0.4; pointer-events: none; }
    .info-row {
        display: flex; padding: 10px 0; border-bottom: 1px solid #EAE6DE; font-family: 'Nunito Sans', sans-serif;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .k { width: 110px; font-size: 12px; font-weight: 600; color: #6B6358; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-row .v { flex: 1; font-size: 14px; color: #2E3230; }
    .empty-note { text-align: center; padding: 28px 12px; color: #6B6358; font-family: 'Nunito Sans', sans-serif; }
    .empty-note i { font-size: 32px; opacity: 0.35; display: block; margin-bottom: 10px; }
</style>
@endsection

@section('content')

@php
    $petsPreview = $pets->take(4);
@endphp

<a href="{{ route('admin.pet-owners.index') }}" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Back to Pet Owners
</a>

{{-- Profile banner --}}
<div class="banner">
    @include('admin.pet-owners._avatar', ['owner' => $owner, 'size' => 88])
    <div style="flex: 1; min-width: 240px;">
        <h2>{{ $owner->name }}</h2>
        <span class="role-badge"><i class="fas fa-user"></i> Pet Owner</span>
        @php $approvalStatus = $owner->approval_status ?? 'approved'; @endphp
        @if($approvalStatus === 'pending')
            <span class="role-badge ms-1" style="background: #F8E0A8; color: #554020;"><i class="fas fa-clock"></i> Pending Staff Approval</span>
        @elseif($approvalStatus === 'rejected')
            <span class="role-badge ms-1" style="background: #FFDAD8; color: #690005;"><i class="fas fa-ban"></i> Rejected</span>
        @else
            <span class="role-badge ms-1" style="background: #C8E8D0; color: #2A6038;"><i class="fas fa-circle-check"></i> Approved</span>
        @endif
        <div class="mt-2">
            @if($owner->email)
                <a href="mailto:{{ $owner->email }}" class="contact-link"><i class="fas fa-envelope"></i>{{ $owner->email }}</a>
            @else
                <span class="contact-link"><i class="fas fa-envelope"></i>—</span>
            @endif
            @if($owner->phone_number)
                <a href="tel:{{ $owner->phone_number }}" class="contact-link"><i class="fas fa-phone"></i>{{ $owner->phone_number }}</a>
            @else
                <span class="contact-link"><i class="fas fa-phone"></i>—</span>
            @endif
            <span class="contact-link"><i class="fas fa-calendar"></i>Member since {{ $owner->created_at->format('F j, Y') }}</span>
        </div>
    </div>
</div>

{{-- Stats --}}
@php
    $statCards = [
        ['Total Pets',         $stats['pets'],         'fa-paw',            '#4A7C59', '#FFFFFF'],
        ['Appointments',       $stats['appointments'], 'fa-calendar-check', '#C8E8D0', '#2A6038'],
        ['Completed Visits',   $stats['completed'],    'fa-check-double',   '#F8E0A8', '#705C30'],
        ['Last Visit',         $stats['last_visit'] ? \Carbon\Carbon::parse($stats['last_visit'])->format('M d, Y') : 'None yet',
                                                       'fa-clock-rotate-left', '#EAE6DE', '#4A4E4A'],
    ];
@endphp
<div class="stat-row">
    @foreach($statCards as [$label, $value, $icon, $bg, $fg])
        <div class="stat">
            <div class="stat-icon" style="background: {{ $bg }}; color: {{ $fg }};"><i class="fas {{ $icon }}"></i></div>
            <div>
                <div class="stat-value" @if(!is_numeric($value)) style="font-size: 18px;" @endif>{{ $value }}</div>
                <div class="stat-label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-8">

        {{-- Pets --}}
        <div class="section-card">
            <h3>
                <span><i class="fas fa-paw me-2" style="color: #4A7C59;"></i>Pets</span>
                @if(auth()->user()->branch !== null && $pets->count() > 4)
                    <a href="{{ route('admin.pets.index', ['search' => $owner->name]) }}">View all {{ $pets->count() }} pets <i class="fas fa-arrow-right ms-1"></i></a>
                @endif
            </h3>
            @if($petsPreview->isEmpty())
                <div class="empty-note"><i class="fas fa-paw"></i>No pets registered yet</div>
            @else
                <div class="pet-grid">
                    @foreach($petsPreview as $pet)
                        @if(auth()->user()->branch !== null)
                            <a href="{{ route('admin.pets.show', $pet->id) }}" class="pet-tile">
                                @include('admin.medical-records._avatar', ['pet' => $pet, 'size' => 48])
                                <div>
                                    <div class="nm">{{ $pet->name }}</div>
                                    <div class="sub">
                                        {{ $pet->species ?: 'Pet' }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}
                                    </div>
                                    <div class="sub">
                                        {{ $pet->medical_records_count }} {{ \Illuminate\Support\Str::plural('record', $pet->medical_records_count) }}
                                    </div>
                                </div>
                            </a>
                        @else
                            <div class="pet-tile">
                                @include('admin.medical-records._avatar', ['pet' => $pet, 'size' => 48])
                                <div>
                                    <div class="nm">{{ $pet->name }}</div>
                                    <div class="sub">
                                        {{ $pet->species ?: 'Pet' }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}
                                    </div>
                                    <div class="sub">
                                        {{ $pet->medical_records_count }} {{ \Illuminate\Support\Str::plural('record', $pet->medical_records_count) }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Appointment history --}}
        <div class="section-card">
            <h3>
                <span><i class="fas fa-calendar-check me-2" style="color: #4A7C59;"></i>Appointment History</span>
                @if(auth()->user()->branch !== null && $stats['appointments'] > $appointments->count())
                    <a href="{{ route('admin.appointments.index', ['search' => $owner->email]) }}">View all {{ $stats['appointments'] }} <i class="fas fa-arrow-right ms-1"></i></a>
                @endif
            </h3>
            @if($appointments->isEmpty())
                <div class="empty-note"><i class="fas fa-calendar-times"></i>No appointments booked yet</div>
            @else
                <div class="table-responsive">
                    <table class="appt-table">
                        <thead>
                            <tr>
                                <th>Pet</th><th>Branch</th><th>Date</th><th>Time</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($appointments as $a)
                                <tr @if(auth()->user()->branch !== null) onclick="window.location='{{ route('admin.appointments.show', $a->id) }}'" @endif>
                                    <td style="font-weight: 600;">{{ $a->pet->name ?? 'Unknown' }}</td>
                                    <td>{{ $a->branch }}</td>
                                    <td>{{ $a->appointment_date ? \Carbon\Carbon::parse($a->appointment_date)->format('M d, Y') : 'Awaiting Staff Date' }}</td>
                                    <td>{{ $a->formatted_time }}</td>
                                    <td><span class="status-pill {{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Actions --}}
        <div class="section-card">
            <h3><span><i class="fas fa-bolt me-2" style="color: #4A7C59;"></i>Actions</span></h3>
            @if(($owner->approval_status ?? 'approved') !== 'approved')
                <form method="POST" action="{{ route('admin.pet-owners.approve', $owner->id) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="action-btn-lg primary w-100 border-0" style="background: #4A7C59; color: #FFFFFF;">
                        <i class="fas fa-check"></i> Approve Account
                    </button>
                </form>
                @if(($owner->approval_status ?? 'approved') !== 'rejected')
                    <form method="POST" action="{{ route('admin.pet-owners.reject', $owner->id) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="action-btn-lg danger w-100" style="background: #FFDAD8; color: #690005; border: 0;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                @endif
            @endif
            <a href="{{ $owner->email ? 'mailto:' . $owner->email : '#' }}"
               class="action-btn-lg primary {{ $owner->email ? '' : 'disabled' }}">
                <i class="fas fa-envelope"></i> Email Owner
            </a>
            <a href="{{ $owner->phone_number ? 'tel:' . $owner->phone_number : '#' }}"
               class="action-btn-lg secondary {{ $owner->phone_number ? '' : 'disabled' }}">
                <i class="fas fa-phone"></i> Call Owner
            </a>
            @if(auth()->user()->branch !== null)
                <a href="{{ route('admin.appointments.index', ['search' => $owner->email ?: $owner->name]) }}"
                   class="action-btn-lg secondary">
                    <i class="fas fa-calendar-check"></i> View Appointments
                </a>
            @endif
            <button type="button" class="action-btn-lg danger mt-3"
                    data-bs-toggle="modal" data-bs-target="#deleteOwnerModal"
                    style="background: #FFF0F0; color: #BA1A1A; border: 1px solid #FFCDD2; cursor: pointer;">
                <i class="fas fa-trash-alt"></i> Delete Client Account
            </button>
        </div>

        {{-- Verification Photo / Valid ID --}}
        <div class="section-card">
            <h3 class="d-flex align-items-center justify-content-between">
                <span><i class="fas fa-id-card-clip me-2" style="color: #4A7C59;"></i>Verification ID</span>
                @if($owner->verification_id_type)
                    <span class="badge rounded-pill" style="background: #F5F1EA; color: #4A4E4A; font-size: 11px; font-weight: 700;">
                        {{ in_array($owner->verification_id_type, ['selfie', 'selfie_with_id'], true) ? 'Clear Selfie' : 'Valid ID' }}
                    </span>
                @endif
            </h3>
            @if(!empty($owner->verification_id_image))
                <div role="button"
                     tabindex="0"
                     data-src="{{ $owner->verification_id_image }}"
                     onclick="openOwnerIdLightbox(this)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openOwnerIdLightbox(this);}"
                     style="background: #F5F1EA; border: 1.5px dashed #D6CFC2; border-radius: 16px; overflow: hidden; cursor: pointer; max-height: 220px; display: flex; align-items: center; justify-content: center;">
                    <img src="{{ $owner->verification_id_image }}"
                         alt="Verification photo for {{ $owner->name }}"
                         style="width: 100%; max-height: 220px; object-fit: contain;">
                </div>
                <button type="button"
                        class="action-btn-lg secondary mt-2 w-100 border-0"
                        data-src="{{ $owner->verification_id_image }}"
                        onclick="openOwnerIdLightbox(this)">
                    <i class="fas fa-search-plus"></i> Zoom &amp; Inspect Full Photo
                </button>
            @else
                <div class="text-muted text-center py-3 px-2" style="background: #FAF8F4; border: 1px dashed #E0DAD0; border-radius: 14px; font-size: 13px;">
                    <i class="far fa-id-card d-block mb-1" style="font-size: 20px; color: #B4AC9E;"></i>
                    No verification photo or ID uploaded.
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="section-card">
            <h3><span><i class="fas fa-id-card me-2" style="color: #4A7C59;"></i>Details</span></h3>
            <div class="info-row">
                <div class="k">Branches</div>
                <div class="v">
                    @forelse($branchesVisited as $b)
                        <span class="branch-chip {{ $branch && $b === $branch ? 'mine' : '' }}">
                            <i class="fas fa-location-dot"></i>{{ \Illuminate\Support\Str::before($b, ' Branch') }}
                        </span>
                    @empty
                        —
                    @endforelse
                </div>
            </div>
            <div class="info-row">
                <div class="k">Verified</div>
                <div class="v">{{ $owner->email_verified_at ? 'Email verified' : 'Not verified' }}</div>
            </div>
            <div class="info-row">
                <div class="k">Joined</div>
                <div class="v">{{ $owner->created_at->diffForHumans() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Client Account Modal --}}
<div class="modal fade" id="deleteOwnerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <form method="POST" action="{{ route('admin.pet-owners.destroy', $owner->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Client Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                    <p class="mb-2">Are you sure you want to delete the account for <strong>{{ $owner->name }}</strong>?</p>
                    <p class="mb-0 text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Deleting this owner will permanently remove all linked pets, appointments, and records. This cannot be undone.</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" style="background: #BA1A1A; border: none;">
                        Delete Client Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Verification Photo Lightbox Modal --}}
@if(!empty($owner->verification_id_image))
<div class="modal fade" id="ownerIdLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #1A1D1A;">
                    <i class="fas fa-id-card-clip me-2" style="color: #4A7C59;"></i> {{ $owner->name }} — Verification Photo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div style="background: #1A1D1A; border-radius: 16px; padding: 12px; display: flex; align-items: center; justify-content: center; max-height: 70vh; overflow: auto;">
                    <img id="ownerIdLightboxImg" src="" alt="Verification ID" style="max-width: 100%; max-height: 64vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    window.openOwnerIdLightbox = function (el) {
        const src = el.getAttribute('data-src');
        const img = document.getElementById('ownerIdLightboxImg');
        const modalEl = document.getElementById('ownerIdLightboxModal');
        if (src && img && modalEl && window.bootstrap) {
            img.src = src;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };
</script>
@endsection
