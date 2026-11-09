@extends('admin.layouts.app')

@section('title', 'Appointment #' . $appointment->id)

@section('styles')
<style>
    .detail-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        margin-bottom: 24px;
    }
    .detail-card h3 {
        font-family: 'Literata', serif;
        font-size: 18px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #EAE6DE;
    }
    .detail-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #EAE6DE;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label {
        width: 180px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .detail-value {
        flex: 1;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
    }
    .header-banner {
        background: #F5F1EA;
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .header-banner h2 {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 4px;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-badge.pending { background: #F8E0A8; color: #554020; }
    .status-badge.approved { background: #C8E8D0; color: #2A6038; }
    .status-badge.completed { background: #EAE6DE; color: #4A4E4A; }
    .status-badge.cancelled { background: #FFDAD8; color: #690005; }

    .action-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
    }
    .action-card h3 {
        font-family: 'Literata', serif;
        font-size: 16px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 16px;
    }
    .action-btn-lg {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 16px;
        border-radius: 24px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        margin-bottom: 10px;
    }
    .action-btn-lg.primary { background: #4A7C59; color: #FFFFFF; }
    .action-btn-lg.primary:hover { background: #3d6b4a; }
    .action-btn-lg.warning { background: #F0E8DB; color: #705C30; }
    .action-btn-lg.warning:hover { background: #e5ddce; }
    .action-btn-lg.danger { background: #FFFFFF; color: #B83230; border: 1px solid rgba(184,50,48,0.3); }
    .action-btn-lg.danger:hover { background: #FFDAD8; }
    .action-btn-lg.secondary { background: #F5F1EA; color: #4A4E4A; }
    .action-btn-lg.secondary:hover { background: #EAE6DE; }
    .action-btn-lg:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .timeline-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #EAE6DE;
    }
    .timeline-item:last-child { border-bottom: none; }
    .timeline-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #4A7C59;
        margin-top: 6px;
        flex-shrink: 0;
    }
    .timeline-date {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        color: #2E3230;
    }
    .timeline-desc {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        color: #6B6358;
    }
</style>
@endsection

@section('content')

{{-- Back Link --}}
<a href="{{ route('admin.appointments.index') }}"
   style="font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #4A7C59; text-decoration: none; margin-bottom: 16px; display: inline-block;">
    <i class="fas fa-arrow-left me-1"></i> Back to Appointments
</a>

{{-- Header Banner --}}
<div class="header-banner">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h2>Appointment #{{ $appointment->id }}</h2>
            <p style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358; margin: 0;">
                Booked on {{ \Carbon\Carbon::parse($appointment->created_at)->format('F j, Y \a\t g:i A') }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="status-badge {{ $appointment->status }}">
                {{ $appointment->status }}
            </span>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1"
                    data-bs-toggle="modal" data-bs-target="#deleteAppointmentModal" title="Delete appointment">
                <i class="fas fa-trash-alt me-1"></i> Delete
            </button>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Left Column: Details --}}
    <div class="col-lg-8">

        {{-- Pet Owner Info --}}
        <div class="detail-card">
            <h3><i class="fas fa-user me-2" style="color: #4A7C59;"></i>Pet Owner</h3>
            <div class="detail-row">
                <div class="detail-label">Name</div>
                <div class="detail-value">{{ $appointment->user->name ?? 'Unknown' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value">{{ $appointment->user->email ?? '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Phone</div>
                <div class="detail-value">{{ $appointment->user->phone_number ?? '—' }}</div>
            </div>
        </div>

        {{-- Pet Info --}}
        <div class="detail-card">
            <h3><i class="fas fa-paw me-2" style="color: #4A7C59;"></i>Pet</h3>
            <div class="detail-row">
                <div class="detail-label">Name</div>
                <div class="detail-value">{{ $appointment->pet->name ?? 'Unknown' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Species</div>
                <div class="detail-value">{{ $appointment->pet->species ?? '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Breed</div>
                <div class="detail-value">{{ $appointment->pet->breed ?? '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Age / Gender</div>
                <div class="detail-value">
                    {{ $appointment->pet->age ?? '—' }}
                    @if($appointment->pet && $appointment->pet->age) yrs @endif
                    • {{ $appointment->pet->gender ?? '—' }}
                </div>
            </div>
        </div>

        {{-- Appointment Details --}}
        <div class="detail-card">
            <h3><i class="fas fa-calendar-check me-2" style="color: #4A7C59;"></i>Appointment Details</h3>
            <div class="detail-row">
                <div class="detail-label">Branch</div>
                <div class="detail-value">{{ $appointment->branch }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date</div>
                <div class="detail-value">
                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, F j, Y') }}
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Time</div>
                <div class="detail-value">{{ $appointment->appointment_time }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Reason</div>
                <div class="detail-value">{{ $appointment->reason ?? '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Veterinarian</div>
                <div class="detail-value">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <span>
                            @if($appointment->veterinarian)
                                <i class="fa-solid fa-user-doctor me-1" style="color: #4A7C59;"></i>
                                <strong>{{ $appointment->veterinarian }}</strong>
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </span>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1"
                                style="background: #F0ECE4; color: #2E3230; font-size: 11px; font-weight: 700; border: 1px solid rgba(196,200,188,0.5);"
                                data-bs-toggle="modal" data-bs-target="#assignVetModal">
                            <i class="fa-solid fa-user-doctor me-1"></i>
                            {{ $appointment->veterinarian ? 'Change Doctor' : 'Assign Doctor' }}
                        </button>
                    </div>
                </div>
            </div>
            @if($appointment->notes)
                @php
                    $isCancelled = $appointment->status === 'cancelled';
                    $hasCancellation = str_contains($appointment->notes, '[Cancelled by Pet Owner]') || str_contains($appointment->notes, '[Cancelled]');
                @endphp
                <div class="detail-row">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value">
                        @if($isCancelled && $hasCancellation)
                            <div class="p-3 rounded-4" style="background: #FFF0F0; border: 1px solid #FFCDD2; color: #8C2424;">
                                <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                    <i class="fas fa-ban"></i> Cancellation Information
                                </div>
                                <div style="white-space: pre-wrap; font-size: 13px;">{{ $appointment->notes }}</div>
                            </div>
                        @else
                            <div style="white-space: pre-wrap;">{{ $appointment->notes }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </div>

    {{-- Right Column: Actions --}}
    <div class="col-lg-4">

        {{-- Action Card --}}
        <div class="action-card mb-4">
            <h3><i class="fas fa-tasks me-2" style="color: #4A7C59;"></i>Actions</h3>

            @if($appointment->status === 'pending')
                <form method="POST" action="{{ route('admin.appointments.approve', $appointment->id) }}">
                    @csrf
                    <button type="submit" class="action-btn-lg primary">
                        <i class="fas fa-check"></i> Approve Appointment
                    </button>
                </form>
            @endif

            @if(in_array($appointment->status, ['pending', 'approved']))
                <form method="POST" action="{{ route('admin.appointments.complete', $appointment->id) }}">
                    @csrf
                    <button type="submit" class="action-btn-lg warning">
                        <i class="fas fa-check-double"></i> Mark as Completed
                    </button>
                </form>
            @endif

            @if(!in_array($appointment->status, ['completed', 'cancelled']))
                <button type="button" class="action-btn-lg secondary"
                        data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                    <i class="fas fa-calendar-alt"></i> Reschedule
                </button>

                <button type="button" class="action-btn-lg danger"
                        data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="fas fa-times"></i> Cancel Appointment
                </button>
            @endif

            @if($appointment->status === 'completed' || $appointment->status === 'cancelled')
                <div class="alert alert-info mb-0" style="font-family: 'Nunito Sans', sans-serif; font-size: 13px;">
                    <i class="fas fa-info-circle me-1"></i>
                    This appointment is {{ $appointment->status }} and cannot be modified.
                </div>
            @endif

            <hr style="border-color: #EAE6DE; margin: 16px 0;">

            <button type="button" class="action-btn-lg danger mb-0"
                    data-bs-toggle="modal" data-bs-target="#deleteAppointmentModal"
                    style="background: #FFF0F0; color: #BA1A1A; border: 1px solid #FFCDD2;">
                <i class="fas fa-trash-alt"></i> Delete Appointment
            </button>
        </div>

        {{-- Timeline Card --}}
        <div class="action-card">
            <h3><i class="fas fa-history me-2" style="color: #4A7C59;"></i>Timeline</h3>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div>
                    <div class="timeline-date">
                        {{ \Carbon\Carbon::parse($appointment->created_at)->format('M d, Y g:i A') }}
                    </div>
                    <div class="timeline-desc">Appointment booked by pet owner</div>
                </div>
            </div>
            @if($appointment->status !== 'pending')
                <div class="timeline-item">
                    <div class="timeline-dot" style="background: #705C30;"></div>
                    <div>
                        <div class="timeline-date">Status: {{ ucfirst($appointment->status) }}</div>
                        <div class="timeline-desc">Current state of this appointment</div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <form method="POST" action="{{ route('admin.appointments.cancel', $appointment->id) }}">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700;">
                        Cancel Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                        Are you sure you want to cancel this appointment? The pet owner will not be notified automatically.
                    </p>
                    <label style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #6B6358;">
                        CANCEL REASON (OPTIONAL)
                    </label>
                    <textarea name="cancel_reason"
                              class="form-control mt-2"
                              rows="3"
                              style="border-radius: 16px; border: 1px solid rgba(196,200,188,0.4); font-family: 'Nunito Sans', sans-serif;"
                              placeholder="e.g., Vet unavailable, pet owner request..."></textarea>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 24px;">Keep Appointment</button>
                    <button type="submit" class="btn btn-danger" style="border-radius: 24px;">Yes, Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reschedule Modal --}}
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <form method="POST" action="{{ route('admin.appointments.reschedule', $appointment->id) }}">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700;">
                        Reschedule Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #6B6358;">
                            NEW DATE
                        </label>
                        <input type="date"
                               name="appointment_date"
                               class="form-control mt-2"
                               style="border-radius: 16px; border: 1px solid rgba(196,200,188,0.4);"
                               min="{{ date('Y-m-d') }}"
                               value="{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d') }}"
                               required>
                    </div>
                    <div class="mb-3">
                        <label style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #6B6358;">
                            NEW TIME
                        </label>
                        <input type="time"
                               name="appointment_time"
                               class="form-control mt-2"
                               style="border-radius: 16px; border: 1px solid rgba(196,200,188,0.4);"
                               value="{{ $appointment->appointment_time }}"
                               required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 24px;">Cancel</button>
                    <button type="submit" class="btn" style="border-radius: 24px; background: #4A7C59; color: white;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Assign Veterinarian Modal --}}
<div class="modal fade" id="assignVetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: 1px solid rgba(196,200,188,0.4);">
            <form method="POST" action="{{ route('admin.appointments.assign-vet', $appointment->id) }}">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230;">
                        <i class="fa-solid fa-user-doctor me-2" style="color: #4A7C59;"></i>
                        Assign Attending Veterinarian
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                            Attending Doctor
                        </label>
                        <select name="veterinarian" class="form-select mt-2"
                                style="border-radius: 14px; padding: 10px 14px; border: 1px solid rgba(196,200,188,0.6); background: #FAF6F0;">
                            <option value="">-- No doctor assigned (Unassigned) --</option>
                            @php
                                $vets = $veterinarians ?? collect();
                            @endphp
                            @foreach($vets as $v)
                                <option value="{{ $v->name }}" @selected($appointment->veterinarian === $v->name)>
                                    {{ $v->name }}{{ $v->specialization ? ' — ' . $v->specialization : '' }}{{ $v->branch ? ' [' . $v->branch . ']' : '' }}
                                </option>
                            @endforeach
                            @if($appointment->veterinarian && !$vets->contains('name', $appointment->veterinarian))
                                <option value="{{ $appointment->veterinarian }}" selected>{{ $appointment->veterinarian }} (Current)</option>
                            @endif
                        </select>
                        <div class="form-text mt-2" style="font-size: 12px; color: #6B6358;">
                            Showing active veterinarians available for {{ $appointment->branch }} and roving doctors.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn rounded-pill px-4 text-white" style="background: #4A7C59; border: none;">
                        <i class="fa-solid fa-check me-1"></i> Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Appointment Modal --}}
<div class="modal fade" id="deleteAppointmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <form method="POST" action="{{ route('admin.appointments.destroy', $appointment->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                    <p class="mb-2">Are you sure you want to delete this appointment? This cannot be undone.</p>
                    <p class="small text-muted mb-2">Pet: <strong>{{ $appointment->pet?->name ?? 'Pet' }}</strong> &bull; Client: <strong>{{ $appointment->user?->name ?? 'Client' }}</strong></p>
                    <div class="p-2 rounded" style="background: #E8F5E9; border: 1px solid rgba(46,125,50,0.25); color: #1B5E20; font-size: 12px;">
                        <i class="fas fa-shield-alt me-1"></i> <strong>Safe Deletion:</strong> The pet profile and medical records will remain completely untouched.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" style="background: #BA1A1A; border: none;">
                        Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
