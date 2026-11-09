@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('styles')
<style>
    .stat-card {
        background: #F5F1EA;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        height: 100%;
    }
    .stat-card .stat-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B6358;
        margin-bottom: 8px;
    }
    .stat-card .stat-value {
        font-family: 'Literata', serif;
        font-size: 32px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1.1;
    }
    .stat-card .stat-footer {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        color: #4A7C59;
        margin-top: 8px;
    }
    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        background: #F0E8DB;
        color: #4A7C59;
    }
    .stat-card.warning .stat-icon { background: rgba(248,224,168,0.5); color: #705C30; }
    .stat-card.success .stat-icon { background: rgba(200,232,208,0.6); color: #2A6038; }
    .stat-card.info .stat-icon { background: rgba(120,168,134,0.3); color: #4A7C59; }
    
    .content-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
    }
    .content-card h3 {
        font-family: 'Literata', serif;
        font-size: 20px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 4px;
    }
    .content-card .subtitle {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        color: #6B6358;
        margin-bottom: 16px;
    }
    .appt-table {
        width: 100%;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
    }
    .appt-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B6358;
        padding: 12px 8px;
        border-bottom: 1px solid #EAE6DE;
    }
    .appt-table td {
        padding: 14px 8px;
        border-bottom: 1px solid #EAE6DE;
        color: #2E3230;
    }
    .appt-table tr:last-child td { border-bottom: none; }
    .status-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pill.pending { background: #F8E0A8; color: #554020; }
    .status-pill.approved { background: #C8E8D0; color: #2A6038; }
    .status-pill.completed { background: #EAE6DE; color: #4A4E4A; }
    .status-pill.cancelled { background: #FFDAD8; color: #690005; }
    
    .branch-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }
    .branch-bar .branch-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        color: #6B6358;
        min-width: 130px;
    }
    .branch-bar .progress {
        flex: 1;
        height: 8px;
        border-radius: 9999px;
        background: #EAE6DE;
    }
    .branch-bar .progress-bar {
        background: #4A7C59;
        border-radius: 9999px;
    }
    .branch-bar .branch-count {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: #2E3230;
        min-width: 40px;
        text-align: right;
    }
</style>
@endsection

@section('content')

{{-- Welcome Header --}}
<div class="mb-4">
    <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
        Welcome, {{ auth()->user()->name }}
    </h2>
    <p style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358; margin: 0;">
        You are viewing:
        <strong style="color: #4A7C59;">
            {{ $branch ?? 'All Branches' }}
        </strong>
    </p>
</div>

{{-- Stat Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-label">Total Appointments</div>
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-value">{{ $totalAppointments }}</div>
            <div class="stat-footer">
                <i class="fas fa-check-circle me-1"></i>
                {{ $completedAppointments }} completed
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-label">Pending Approvals</div>
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-value">{{ $pendingAppointments }}</div>
            <div class="stat-footer" style="color: #705C30;">
                <i class="fas fa-clock me-1"></i>
                Awaiting review
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-label">Approved</div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-value">{{ $approvedAppointments }}</div>
            <div class="stat-footer" style="color: #2A6038;">
                <i class="fas fa-arrow-up me-1"></i>
                Scheduled visits
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-label">Registered Pets</div>
                <div class="stat-icon"><i class="fas fa-paw"></i></div>
            </div>
            <div class="stat-value">{{ $totalPets }}</div>
            <div class="stat-footer">
                <a href="{{ route('admin.pet-owners.index') }}" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-users me-1"></i>
                    {{ $totalPetOwners }} pet owners
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Two-column area --}}
<div class="row g-4">

    {{-- Recent Appointments --}}
    <div class="col-lg-8">
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3>Recent Appointments</h3>
                    <div class="subtitle">The latest bookings from pet owners</div>
                </div>
                <a href="{{ Route::has('admin.appointments.index') ? route('admin.appointments.index') : url('/admin/appointments') }}" 
                   style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #4A7C59; text-decoration: none;">
                    See All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            @if($recentAppointments->isEmpty())
                <div class="text-center py-5" style="color: #6B6358;">
                    <i class="fas fa-calendar-times fa-2x mb-3" style="opacity: 0.4;"></i>
                    <p style="font-family: 'Nunito Sans', sans-serif; margin: 0;">
                        No appointments yet
                    </p>
                </div>
            @else
                <table class="appt-table">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th>Pet</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentAppointments as $appt)
                            <tr>
                                <td>{{ $appt->user->name ?? 'Unknown' }}</td>
                                <td>
                                    <i class="fas fa-paw me-1" style="color: #4A7C59; font-size: 11px;"></i>
                                    {{ $appt->pet->name ?? 'Unknown' }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($appt->appointment_date)->format('M d, Y') }}
                                    <div style="font-size: 11px; color: #6B6358;">
                                        {{ $appt->appointment_time }}
                                    </div>
                                </td>
                                <td>
                                    <span class="status-pill {{ $appt->status }}">
                                        {{ ucfirst($appt->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Appointments by Branch --}}
    <div class="col-lg-4">
        <div class="content-card">
            <h3>Appointments by Branch</h3>
            <div class="subtitle">Total bookings per location</div>

            @php
                $maxCount = $appointmentsByBranch->max('total') ?: 1;
            @endphp

            @foreach($appointmentsByBranch as $branchData)
                <div class="branch-bar">
                    <div class="branch-label">
                        {{ $branchData->branch }}
                    </div>
                    <div class="progress">
                        <div class="progress-bar" 
                             style="width: {{ ($branchData->total / $maxCount) * 100 }}%;"></div>
                    </div>
                    <div class="branch-count">{{ $branchData->total }}</div>
                </div>
            @endforeach

            @if($appointmentsByBranch->isEmpty())
                <p style="font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #6B6358; text-align: center; padding: 20px 0; margin: 0;">
                    No appointments yet
                </p>
            @endif
        </div>
    </div>

</div>

@endsection
