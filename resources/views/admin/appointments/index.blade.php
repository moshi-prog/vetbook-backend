@extends('admin.layouts.app')

@section('title', 'Appointments')

@section('styles')
<style>
    .filter-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .filter-tab {
        padding: 8px 16px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-tab:hover { background: #EAE6DE; color: #2E3230; }
    .filter-tab.active { background: #4A7C59; color: #FFFFFF; }
    .filter-tab .count {
        background: rgba(255,255,255,0.3);
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 11px;
    }
    .filter-tab:not(.active) .count {
        background: rgba(107,99,88,0.15);
        color: #6B6358;
    }
    .search-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
    }
    .search-bar input {
        flex: 1;
        padding: 10px 16px 10px 40px;
        border-radius: 16px;
        border: 1px solid rgba(196,200,188,0.4);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
    }
    .search-bar input:focus {
        outline: none;
        border-color: #4A7C59;
        box-shadow: 0 0 0 3px rgba(74,124,89,0.1);
    }
    .search-bar .input-wrap {
        position: relative;
        flex: 1;
    }
    .search-bar .input-wrap i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B6358;
        font-size: 14px;
    }
    .appt-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
    }
    .appt-table {
        width: 100%;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        margin: 0;
    }
    .appt-table thead {
        background: #F5F1EA;
    }
    .appt-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B6358;
        padding: 16px;
        border-bottom: 1px solid #EAE6DE;
        white-space: nowrap;
    }
    .appt-table td {
        padding: 18px 16px;
        border-bottom: 1px solid #EAE6DE;
        color: #2E3230;
        vertical-align: middle;
    }
    .appt-table tr:last-child td { border-bottom: none; }
    .appt-table tr:hover { background: rgba(245,241,234,0.4); }
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
    .pet-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #F0ECE4;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #4A7C59;
        font-size: 14px;
        margin-right: 10px;
    }
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #6B6358;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .action-btn:hover { background: #F0ECE4; color: #2E3230; }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6B6358;
    }
    .empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 16px; }
    .empty-state h4 {
        font-family: 'Literata', serif;
        font-size: 18px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 8px;
    }
    .pagination-wrap {
        padding: 20px;
        border-top: 1px solid #EAE6DE;
        background: #FAF6F0;
    }
    .pagination-wrap .pagination {
        margin-bottom: 0;
    }
    .pagination-wrap .page-item.active .page-link {
        background-color: #4A7C59;
        border-color: #4A7C59;
        color: #FFFFFF;
    }
    .pagination-wrap .page-link {
        color: #4A7C59;
        border-radius: 8px;
        margin: 0 2px;
        border-color: #EAE6DE;
    }
</style>
@endsection

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
            Appointments
        </h2>
        <p style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358; margin: 0;">
            @if($branch)
                Viewing appointments for <strong style="color: #4A7C59;">{{ $branch }}</strong>
            @else
                Viewing all appointments across all branches
            @endif
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.appointments.export', request()->query()) }}" target="_blank"
           class="filter-tab" style="background: #4A7C59; color: #FFFFFF; font-weight: 700; padding: 10px 20px; text-decoration: none;"
           onmouseover="this.style.background='#3d6b4a'" onmouseout="this.style.background='#4A7C59'">
            <i class="fas fa-print me-1"></i> Export / Print Report
        </a>
    </div>
</div>

{{-- Filter Tabs --}}
<div class="filter-tabs">
    @php
        $currentParams = array_filter(request()->only(['search', 'branch', 'date', 'date_from', 'date_to', 'sort']), fn($val) => $val !== null && $val !== '');
    @endphp
    <a href="{{ route('admin.appointments.index', array_merge($currentParams, ['status' => 'all'])) }}"
       class="filter-tab {{ !request('status') || request('status') == 'all' ? 'active' : '' }}">
        All <span class="count">{{ $stats['all'] }}</span>
    </a>
    <a href="{{ route('admin.appointments.index', array_merge($currentParams, ['status' => 'pending'])) }}"
       class="filter-tab {{ request('status') == 'pending' ? 'active' : '' }}">
        Pending <span class="count">{{ $stats['pending'] }}</span>
    </a>
    <a href="{{ route('admin.appointments.index', array_merge($currentParams, ['status' => 'approved'])) }}"
       class="filter-tab {{ request('status') == 'approved' ? 'active' : '' }}">
        Approved <span class="count">{{ $stats['approved'] }}</span>
    </a>
    <a href="{{ route('admin.appointments.index', array_merge($currentParams, ['status' => 'completed'])) }}"
       class="filter-tab {{ request('status') == 'completed' ? 'active' : '' }}">
        Completed <span class="count">{{ $stats['completed'] }}</span>
    </a>
    <a href="{{ route('admin.appointments.index', array_merge($currentParams, ['status' => 'cancelled'])) }}"
       class="filter-tab {{ request('status') == 'cancelled' ? 'active' : '' }}">
        Cancelled <span class="count">{{ $stats['cancelled'] }}</span>
    </a>
</div>

{{-- Filters Form (Search, Branch, Date Range, Sort) --}}
<form method="GET" action="{{ route('admin.appointments.index') }}" class="mb-4">
    @if(request()->filled('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
    @endif

    <div class="row g-2 align-items-center">
        {{-- Search input --}}
        <div class="{{ !$branch ? 'col-md-3' : 'col-md-4' }}">
            <div class="search-bar" style="margin-bottom: 0;">
                <div class="input-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search pet, owner, phone..." 
                           value="{{ request('search') }}">
                </div>
            </div>
        </div>

        {{-- Branch filter (for Super Admin) --}}
        @if(!$branch)
            <div class="col-md-2">
                <select name="branch" class="form-select" style="border-radius: 16px; height: 42px; border: 1px solid rgba(196,200,188,0.4); background: #FAF6F0; font-family: 'Nunito Sans', sans-serif; font-size: 13px;" onchange="this.form.submit()">
                    <option value="all" {{ !request('branch') || request('branch') == 'all' ? 'selected' : '' }}>All Branches</option>
                    <option value="Mabini Branch" {{ request('branch') == 'Mabini Branch' ? 'selected' : '' }}>Mabini Branch</option>
                    <option value="Batangas City Branch" {{ request('branch') == 'Batangas City Branch' ? 'selected' : '' }}>Batangas City Branch</option>
                </select>
            </div>
        @endif

        {{-- Date filter shortcut --}}
        <div class="{{ !$branch ? 'col-md-2' : 'col-md-3' }}">
            <div class="d-flex gap-2">
                <input type="date" name="date" class="form-control" style="border-radius: 16px; height: 42px; border: 1px solid rgba(196,200,188,0.4); background: #FAF6F0; font-family: 'Nunito Sans', sans-serif; font-size: 13px;" 
                       value="{{ request('date') }}" title="Specific Date">
            </div>
        </div>

        {{-- Sort filter --}}
        <div class="col-md-3">
            <select name="sort" class="form-select" style="border-radius: 16px; height: 42px; border: 1px solid rgba(196,200,188,0.4); background: #FAF6F0; font-family: 'Nunito Sans', sans-serif; font-size: 13px;" onchange="this.form.submit()">
                <option value="newest_booked" {{ ($sort ?? request('sort', 'newest_booked')) === 'newest_booked' ? 'selected' : '' }}>Sort: Newest Booked</option>
                <option value="appointment_date" {{ ($sort ?? request('sort')) === 'appointment_date' ? 'selected' : '' }}>Sort: Appointment Date</option>
                <option value="oldest" {{ ($sort ?? request('sort')) === 'oldest' ? 'selected' : '' }}>Sort: Oldest First</option>
            </select>
        </div>

        {{-- Submit & Clear Buttons --}}
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="filter-tab active" style="padding: 10px 18px; border-radius: 16px; flex: 1; justify-content: center;">
                Filter
            </button>
            @if(request('search') || request('branch') || request('date') || (request('sort') && request('sort') !== 'newest_booked'))
                <a href="{{ route('admin.appointments.index', array_filter(['status' => request('status')])) }}" 
                   class="filter-tab" style="padding: 10px 14px; border-radius: 16px; justify-content: center;" title="Reset filters">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- Live Table Sync Alert Banner --}}
<div id="liveNewBookingBanner" style="display: none; cursor: pointer; border-radius: 16px; background: #E8F5E9; border: 1px solid rgba(46,125,50,0.3); color: #1B5E20; padding: 14px 20px;" class="mb-3 d-flex align-items-center justify-content-between shadow-sm" onclick="window.location.reload();">
    <div class="d-flex align-items-center gap-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 36px; height: 36px; background: #C8E8D0; color: #1B5E20; font-size: 16px;">
            <i class="fas fa-bell"></i>
        </span>
        <div>
            <div style="font-family: 'Literata', serif; font-weight: 700; font-size: 14px;">
                New appointment received just now!
            </div>
            <div style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #2E7D32;">
                Click here to refresh the table and view the latest booking.
            </div>
        </div>
    </div>
    <span class="btn btn-sm text-white rounded-pill px-3 py-1" style="background: #4A7C59; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px;">
        <i class="fas fa-arrow-rotate-right me-1"></i> Refresh Table
    </span>
</div>

{{-- Appointments Table --}}
<div class="appt-card">
    @if($appointments->isEmpty())
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h4>No appointments found</h4>
            <p style="font-family: 'Nunito Sans', sans-serif; margin: 0;">
                @if(request('search'))
                    Try adjusting your search or filters.
                @else
                    New appointments will appear here as pet owners book them.
                @endif
            </p>
        </div>
    @else
        <div class="table-responsive">
            <table class="appt-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAll" title="Select All" style="cursor: pointer; width: 16px; height: 16px; accent-color: #4A7C59;">
                        </th>
                        <th>Pet Owner</th>
                        <th>Pet</th>
                        <th>Branch</th>
                        <th>Date & Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $appt)
                        <tr>
                            <td style="width: 40px; text-align: center;" onclick="event.stopPropagation()">
                                <input type="checkbox" name="selected_ids[]" value="{{ $appt->id }}" class="row-checkbox" style="cursor: pointer; width: 16px; height: 16px; accent-color: #4A7C59;">
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $appt->user->name ?? 'Unknown' }}</div>
                                <div style="font-size: 11px; color: #6B6358;">
                                    {{ $appt->user->email ?? '' }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="pet-avatar">
                                        <i class="fas fa-paw"></i>
                                    </span>
                                    <div>
                                        <div style="font-weight: 600;">{{ $appt->pet->name ?? 'Unknown' }}</div>
                                        <div style="font-size: 11px; color: #6B6358;">
                                            {{ $appt->pet->species ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 12px;">{{ $appt->branch }}</span>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    {{ \Carbon\Carbon::parse($appt->appointment_date)->format('M d, Y') }}
                                </div>
                                <div style="font-size: 11px; color: #6B6358;">
                                    {{ $appt->formatted_time }}
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 12px;">{{ $appt->reason ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="status-pill {{ $appt->status }}">
                                    {{ ucfirst($appt->status) }}
                                </span>
                                @if($appt->status === 'cancelled' && !empty($appt->notes))
                                    @php
                                        $reasonText = null;
                                        if (preg_match('/\[Cancelled by Pet Owner\]\s*Reason:\s*([^\n\r]+)/i', $appt->notes, $matches)) {
                                            $reasonText = trim($matches[1]);
                                        } elseif (preg_match('/\[Cancelled\]\s*([^\n\r]+)/i', $appt->notes, $matches)) {
                                            $reasonText = trim($matches[1]);
                                        }
                                    @endphp
                                    @if($reasonText)
                                        <div class="mt-1 fst-italic" style="font-size: 11px; color: #8C2424; max-width: 180px; line-height: 1.25;" title="{{ $reasonText }}">
                                            <i class="fas fa-info-circle me-1"></i><span style="font-weight: 600;">Reason:</span> {{ \Illuminate\Support\Str::limit($reasonText, 40) }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('admin.appointments.show', $appt->id) }}"
                                       class="action-btn" title="View details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="action-btn text-danger" title="Delete appointment"
                                            data-bs-toggle="modal" data-bs-target="#deleteApptModal-{{ $appt->id }}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade text-start" id="deleteApptModal-{{ $appt->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content" style="border-radius: 24px; border: none;">
                                            <form method="POST" action="{{ route('admin.appointments.destroy', $appt->id) }}">
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
                                                    <p class="small text-muted mb-2">Pet: <strong>{{ $appt->pet?->name ?? 'Pet' }}</strong> &bull; Client: <strong>{{ $appt->user?->name ?? 'Client' }}</strong></p>
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $appointments->links() }}
        </div>
    @endif
</div>

@include('admin.common._batch_actions', [
    'module' => 'appointments',
    'itemName' => 'appointment(s)',
    'bulkDeleteRoute' => route('admin.appointments.bulk-delete'),
    'exportRoute' => route('admin.appointments.export'),
])

{{-- Live Table Poller for Newly Booked Appointments --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    @php
        $maxLoadedId = $appointments->isNotEmpty() ? $appointments->max('id') : 0;
    @endphp
    let initialMaxId = {{ (int) $maxLoadedId }};
    const banner = document.getElementById('liveNewBookingBanner');
    const branchParam = '{{ request('branch', '') }}';

    async function checkLatestAppointment() {
        try {
            const url = '{{ route('admin.appointments.latest-id') }}' + (branchParam ? ('?branch=' + encodeURIComponent(branchParam)) : '');
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            const latestId = parseInt(data.latest_id, 10);

            if (latestId > initialMaxId) {
                if (banner && banner.style.display === 'none') {
                    banner.style.display = 'flex';
                    if (window.playAdminChime) {
                        try { window.playAdminChime(); } catch (e) {}
                    }
                }
            }
        } catch (err) {
            // Silently ignore network hiccups
        }
    }

    // Poll every 12 seconds
    setInterval(checkLatestAppointment, 12000);
});
</script>

@endsection
