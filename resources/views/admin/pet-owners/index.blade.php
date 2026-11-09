@extends('admin.layouts.app')

@section('title', 'Pet Owners')

@section('styles')
<style>
    .po-subtitle { font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358; margin: 0; }
    .branch-note {
        display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 5px 14px;
        border-radius: 9999px; background: #C8E8D0; color: #2A6038;
        font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700;
    }

    /* Stats */
    .po-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .po-stat {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); display: flex; align-items: center; gap: 16px;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .po-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(46,50,48,0.08); }
    .po-stat-icon {
        width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center;
        justify-content: center; font-size: 20px; flex-shrink: 0;
    }
    .po-stat-value { font-family: 'Literata', serif; font-size: 28px; font-weight: 700; color: #2E3230; line-height: 1; }
    .po-stat-label {
        font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #6B6358;
        text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;
    }
    @media (max-width: 991px) { .po-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .po-stats { grid-template-columns: 1fr; } }

    /* Filters */
    .filter-panel {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); margin-bottom: 24px;
    }
    .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .filter-tab {
        padding: 8px 16px; border-radius: 9999px; background: #F5F1EA; color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 600; text-decoration: none;
        border: none; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px;
    }
    .filter-tab:hover { background: #EAE6DE; color: #2E3230; }
    .filter-tab.active { background: #4A7C59; color: #FFFFFF; }
    .search-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .search-row .input-wrap { position: relative; flex: 1; min-width: 240px; }
    .search-row .input-wrap i {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6B6358; font-size: 14px;
    }
    .search-row input[type="text"], .search-row select {
        padding: 10px 16px; border-radius: 16px; border: 1px solid rgba(196,200,188,0.4);
        background: #FAF6F0; font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #2E3230;
    }
    .search-row input[type="text"] { width: 100%; padding-left: 40px; }
    .search-row input:focus, .search-row select:focus {
        outline: none; border-color: #4A7C59; box-shadow: 0 0 0 3px rgba(74,124,89,0.1);
    }
    .clear-link {
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 600; color: #B83230;
        text-decoration: none; padding: 10px 14px; border-radius: 9999px; transition: background 0.15s;
    }
    .clear-link:hover { background: #FFDAD8; color: #B83230; }

    /* Owner cards */
    .owner-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media (max-width: 1199px) { .owner-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 767px)  { .owner-grid { grid-template-columns: 1fr; } }
    .owner-card {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 22px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); cursor: pointer; display: flex; flex-direction: column;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .owner-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(46,50,48,0.09); }
    .owner-avatar {
        border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Literata', serif; font-weight: 700; flex-shrink: 0; position: relative; overflow: hidden;
    }
    .owner-avatar img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .avatar-wrap { position: relative; display: inline-block; }
    .activity-dot {
        position: absolute; right: -1px; bottom: -1px; width: 14px; height: 14px; border-radius: 50%;
        border: 3px solid #FFFFFF; background: #C4C0B6;
    }
    .activity-dot.month { background: #4A7C59; }
    .activity-dot.year { background: #D9A93F; }
    .owner-name {
        font-family: 'Literata', serif; font-size: 17px; font-weight: 700; color: #2E3230;
        text-decoration: none; line-height: 1.2; display: block; word-break: break-word;
    }
    .owner-name:hover { color: #4A7C59; }
    .owner-since { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; }
    .contact-line {
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #4A4E4A; display: flex;
        align-items: center; gap: 10px; margin-top: 8px; word-break: break-all;
    }
    .contact-line i { width: 14px; color: #78A886; text-align: center; flex-shrink: 0; }
    .owner-meta {
        display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 16px; padding-top: 14px;
        border-top: 1px solid #EAE6DE;
    }
    .pet-badge, .appt-badge, .branch-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif; font-size: 11px; font-weight: 700;
    }
    .pet-badge { background: #C8E8D0; color: #2A6038; }
    .pet-badge.none { background: #EAE6DE; color: #6B6358; }
    .appt-badge { background: #F0ECE4; color: #4A4E4A; }
    .branch-chip { background: #F5F1EA; color: #4A4E4A; font-weight: 600; padding: 3px 10px; }
    .branch-chip.mine { background: #C8E8D0; color: #2A6038; }
    .last-booking {
        margin-top: 12px; font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358;
        display: flex; justify-content: space-between; align-items: center;
    }
    .last-booking strong { color: #2E3230; }

    /* Legend */
    .legend { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; margin-bottom: 14px; display: flex; gap: 16px; flex-wrap: wrap; }
    .legend span { display: inline-flex; align-items: center; gap: 6px; }
    .legend i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

    /* Empty + pagination */
    .po-card {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); margin-bottom: 24px;
    }
    .empty-state { text-align: center; padding: 64px 20px; }
    .empty-icon {
        width: 96px; height: 96px; border-radius: 50%; background: #F0ECE4; color: #78A886;
        display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px;
    }
    .empty-state h4 { font-family: 'Literata', serif; font-size: 20px; font-weight: 700; color: #2E3230; margin-bottom: 8px; }
    .empty-state p { font-family: 'Nunito Sans', sans-serif; color: #6B6358; margin: 0 0 16px; }
    .pagination-wrap {
        padding: 16px 20px; border-radius: 24px; background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .pagination-info { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; }
    .pagination-wrap .pagination { margin-bottom: 0; }
    .pagination-wrap .page-item.active .page-link { background-color: #4A7C59; border-color: #4A7C59; color: #FFFFFF; }
    .pagination-wrap .page-link { color: #4A7C59; border-radius: 8px; margin: 0 2px; border-color: #EAE6DE; }
</style>
@endsection

@section('content')

@php
    $filterTabs = [
        'all'    => ['All owners', 'fa-users'],
        'new'    => ['New this month', 'fa-user-plus'],
        'active' => ['Active this month', 'fa-calendar-check'],
        'pets'   => ['Has pets', 'fa-paw'],
    ];
    $tabUrl = fn ($key) => route('admin.pet-owners.index', array_filter([
        'filter' => $key === 'all' ? null : $key,
        'search' => $filters['search'],
        'sort'   => $filters['sort'] !== 'newest' ? $filters['sort'] : null,
    ]));
    $statCards = [
        ['Total Pet Owners',  $stats['total'],             'fa-users',          '#4A7C59', '#FFFFFF'],
        ['New This Month',    $stats['new_this_month'],    'fa-user-plus',      '#C8E8D0', '#2A6038'],
        ['Active This Month', $stats['active_this_month'], 'fa-calendar-check', '#F8E0A8', '#705C30'],
        ['Multiple Pets',     $stats['multi_pets'],        'fa-paw',            '#EAE6DE', '#4A4E4A'],
    ];
    $today = \Carbon\Carbon::today();
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">Pet Owners</h2>
        <p class="po-subtitle">
            <strong style="color: #2E3230;">{{ number_format($stats['total']) }}</strong>
            registered pet {{ \Illuminate\Support\Str::plural('owner', $stats['total']) }}
        </p>
        <div class="branch-note">
            <i class="fas fa-location-dot"></i>
            @if($branch)
                Showing all registered owners &middot; {{ $branch }}
            @else
                Showing all owners across all branches
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.pet-owners.export', request()->query()) }}" target="_blank"
           class="btn" style="background: #4A7C59; color: #FFFFFF; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; padding: 10px 20px; border-radius: 9999px; text-decoration: none;"
           onmouseover="this.style.background='#3d6b4a'" onmouseout="this.style.background='#4A7C59'">
            <i class="fas fa-print me-1"></i> Export / Print Report
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="po-stats">
    @foreach($statCards as [$label, $value, $icon, $bg, $fg])
        <div class="po-stat">
            <div class="po-stat-icon" style="background: {{ $bg }}; color: {{ $fg }};"><i class="fas {{ $icon }}"></i></div>
            <div>
                <div class="po-stat-value">{{ number_format($value) }}</div>
                <div class="po-stat-label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="filter-panel">
    <div class="filter-tabs">
        @foreach($filterTabs as $key => [$label, $icon])
            <a href="{{ $tabUrl($key) }}" class="filter-tab {{ $filters['filter'] === $key ? 'active' : '' }}">
                <i class="fas {{ $icon }}"></i> {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.pet-owners.index') }}" class="search-row">
        @if($filters['filter'] !== 'all')
            <input type="hidden" name="filter" value="{{ $filters['filter'] }}">
        @endif
        <div class="input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by name, email, or phone...">
        </div>
        <select name="sort" onchange="this.form.submit()" aria-label="Sort owners">
            <option value="newest"       @selected($filters['sort'] === 'newest')>Newest first</option>
            <option value="name"         @selected($filters['sort'] === 'name')>Name A–Z</option>
            <option value="pets"         @selected($filters['sort'] === 'pets')>Most pets</option>
            <option value="appointments" @selected($filters['sort'] === 'appointments')>Most appointments</option>
        </select>
        <button type="submit" class="filter-tab active" style="padding: 10px 24px;">Search</button>
        @if($hasFilters)
            <a href="{{ route('admin.pet-owners.index') }}" class="clear-link"><i class="fas fa-xmark me-1"></i> Clear filters</a>
        @endif
    </form>
</div>

@if($owners->isEmpty())
    <div class="po-card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-users"></i></div>
            <h4>No pet owners found</h4>
            @if($hasFilters)
                <p>Try adjusting your search or filters.</p>
                <a href="{{ route('admin.pet-owners.index') }}" class="filter-tab active">Clear filters</a>
            @else
                <p>Owners will appear here once they register in the VetBook app.</p>
            @endif
        </div>
    </div>
@else
    <div class="legend">
        <span><i style="background: #4A7C59;"></i> Booked this month</span>
        <span><i style="background: #D9A93F;"></i> Booked this year</span>
        <span><i style="background: #C4C0B6;"></i> No recent bookings</span>
    </div>

    <div class="d-flex align-items-center justify-content-between p-3 mb-3 bg-white rounded-4 border" style="box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
        <label class="d-flex align-items-center gap-2 mb-0" style="cursor: pointer; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; color: #4A4E4A;">
            <input type="checkbox" id="selectAll" style="cursor: pointer; width: 18px; height: 18px; accent-color: #4A7C59;">
            Select All Pet Owners on Page
        </label>
        <span class="text-muted small">Check multiple owners to perform bulk actions</span>
    </div>

    <div class="owner-grid">
        @foreach($owners as $owner)
            @php
                $last = $owner->last_booking ? \Carbon\Carbon::parse($owner->last_booking) : null;
                $dot = $last
                    ? ($last->isSameMonth($today) ? 'month' : ($last->isSameYear($today) ? 'year' : ''))
                    : '';
                $visited = $branchesByOwner[$owner->id] ?? collect();
            @endphp
            <div class="owner-card" onclick="window.location='{{ route('admin.pet-owners.show', $owner->id) }}'">
                <div class="d-flex align-items-center gap-3">
                    <input type="checkbox" name="selected_ids[]" value="{{ $owner->id }}" class="row-checkbox"
                           style="cursor: pointer; width: 18px; height: 18px; accent-color: #4A7C59; flex-shrink: 0;"
                           title="Select owner" onclick="event.stopPropagation()">
                    <span class="avatar-wrap">
                        @include('admin.pet-owners._avatar', ['owner' => $owner, 'size' => 52])
                        <span class="activity-dot {{ $dot }}"></span>
                    </span>
                    <div style="min-width: 0;">
                        <a href="{{ route('admin.pet-owners.show', $owner->id) }}" class="owner-name"
                           onclick="event.stopPropagation()">{{ $owner->name }}</a>
                        <div class="owner-since">Member since {{ $owner->created_at->format('M Y') }}</div>
                    </div>
                </div>

                <div class="mt-2">
                    <div class="contact-line"><i class="fas fa-envelope"></i>{{ $owner->email ?: '—' }}</div>
                    <div class="contact-line"><i class="fas fa-phone"></i>{{ $owner->phone_number ?: '—' }}</div>
                </div>

                <div class="owner-meta">
                    <span class="pet-badge {{ $owner->pets_count ? '' : 'none' }}">
                        <i class="fas fa-paw"></i>
                        {{ $owner->pets_count }} {{ \Illuminate\Support\Str::plural('pet', $owner->pets_count) }}
                    </span>
                    <span class="appt-badge">
                        <i class="fas fa-calendar-check"></i>
                        {{ $owner->appointments_count }} {{ \Illuminate\Support\Str::plural('appt', $owner->appointments_count) }}
                    </span>
                    @if($visited->isEmpty())
                        <span class="branch-chip" style="background: #EAE6DE; color: #6B6358;">
                            <i class="fas fa-user-plus"></i>New Client
                        </span>
                    @else
                        @foreach($visited as $b)
                            <span class="branch-chip {{ $branch && $b === $branch ? 'mine' : '' }}">
                                <i class="fas fa-location-dot"></i>{{ \Illuminate\Support\Str::before($b, ' Branch') }}
                            </span>
                        @endforeach
                    @endif
                </div>

                <div class="last-booking d-flex align-items-center justify-content-between">
                    <span>
                        Last booking:
                        <strong>{{ $last ? $last->format('M d, Y') : 'None yet' }}</strong>
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm text-danger p-0 border-0"
                                title="Delete Client Account"
                                onclick="event.stopPropagation();"
                                data-bs-toggle="modal" data-bs-target="#deleteOwnerModal-{{ $owner->id }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <i class="fas fa-chevron-right" style="color: #78A886;"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@foreach($owners as $owner)
    <!-- Delete Owner Modal -->
    <div class="modal fade text-start" id="deleteOwnerModal-{{ $owner->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation();">
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
                    <div class="modal-body" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
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
@endforeach

    <div class="pagination-wrap">
        <div class="pagination-info">Showing {{ $owners->firstItem() }}–{{ $owners->lastItem() }} of {{ $owners->total() }}</div>
        {{ $owners->links() }}
    </div>

    @include('admin.common._batch_actions', [
        'module' => 'pet-owners',
        'itemName' => 'pet owner(s)',
        'bulkDeleteRoute' => route('admin.pet-owners.bulk-delete'),
        'exportRoute' => route('admin.pet-owners.export'),
    ])
@endif

@endsection
