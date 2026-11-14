@extends('admin.layouts.app')

@section('title', 'Pets')

@php
    // Species presentation: [bg, text, icon, avatar bg, avatar text]
    $speciesStyles = [
        'dog'    => ['#C8E8D0', '#2A6038', 'fa-dog',   '#C8E8D0', '#2A6038'],
        'cat'    => ['#F8E0A8', '#705C30', 'fa-cat',   '#F8E0A8', '#705C30'],
        'bird'   => ['#D8E6F0', '#35607E', 'fa-dove',  '#D8E6F0', '#35607E'],
        'rabbit' => ['#FFDAD8', '#B83230', 'fa-carrot','#FFDAD8', '#B83230'],
        'other'  => ['#EAE6DE', '#4A4E4A', 'fa-paw',   '#EAE6DE', '#4A4E4A'],
    ];
    $styleFor = function ($species) use ($speciesStyles) {
        $key = strtolower((string) $species);
        return $speciesStyles[$key] ?? $speciesStyles['other'];
    };

    $tabs = [
        'all'    => ['All', 'fa-paw'],
        'dog'    => ['Dogs', 'fa-dog'],
        'cat'    => ['Cats', 'fa-cat'],
        'bird'   => ['Birds', 'fa-dove'],
        'rabbit' => ['Rabbits', 'fa-carrot'],
        'other'  => ['Other', 'fa-ellipsis'],
    ];

    $tabUrl = function ($sp) use ($filters) {
        return route('admin.pets.index', array_filter([
            'species' => $sp === 'all' ? null : $sp,
            'search'  => $filters['search'],
            'sort'    => $filters['sort'] !== 'newest' ? $filters['sort'] : null,
        ]));
    };
@endphp

@section('styles')
<style>
    /* ---------- Header ---------- */
    .pets-subtitle {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #6B6358;
        margin: 0;
    }
    .branch-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        padding: 5px 14px;
        border-radius: 9999px;
        background: #C8E8D0;
        color: #2A6038;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }

    /* ---------- Stat cards ---------- */
    .pet-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .pet-stat {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .pet-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(46,50,48,0.08); }
    .pet-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .pet-stat-value {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1;
    }
    .pet-stat-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 6px;
    }

    /* ---------- Filter bar ---------- */
    .filter-panel {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        margin-bottom: 24px;
    }
    .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
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
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 11px;
        background: rgba(107,99,88,0.15);
        color: #6B6358;
    }
    .filter-tab.active .count { background: rgba(255,255,255,0.3); color: #FFFFFF; }

    .search-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .search-row .input-wrap { position: relative; flex: 1; min-width: 240px; }
    .search-row .input-wrap i {
        position: absolute; left: 14px; top: 50%;
        transform: translateY(-50%);
        color: #6B6358; font-size: 14px;
    }
    .search-row input[type="text"],
    .search-row select {
        padding: 10px 16px;
        border-radius: 16px;
        border: 1px solid rgba(196,200,188,0.4);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
    }
    .search-row input[type="text"] { width: 100%; padding-left: 40px; }
    .search-row select { cursor: pointer; }
    .search-row input:focus, .search-row select:focus {
        outline: none;
        border-color: #4A7C59;
        box-shadow: 0 0 0 3px rgba(74,124,89,0.1);
    }
    .clear-link {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        color: #B83230;
        text-decoration: none;
        padding: 10px 14px;
        border-radius: 9999px;
        transition: background 0.15s;
    }
    .clear-link:hover { background: #FFDAD8; color: #B83230; }

    /* ---------- Table ---------- */
    .pets-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
    }
    .pets-table { width: 100%; font-family: 'Nunito Sans', sans-serif; font-size: 13px; margin: 0; }
    .pets-table thead { background: #F5F1EA; }
    .pets-table th {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.6px; color: #6B6358; padding: 16px;
        border-bottom: 1px solid #EAE6DE; white-space: nowrap;
    }
    .pets-table td {
        padding: 16px;
        border-bottom: 1px solid #EAE6DE;
        color: #2E3230;
        vertical-align: middle;
    }
    .pets-table tbody tr { cursor: pointer; transition: background 0.15s; }
    .pets-table tbody tr:hover { background: rgba(245,241,234,0.6); }
    .pets-table tbody tr:last-child td { border-bottom: none; }

    .pet-avatar {
        position: relative;
        width: 46px; height: 46px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Literata', serif;
        font-size: 18px; font-weight: 700;
        flex-shrink: 0;
        overflow: hidden;
    }
    .pet-avatar img {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .pet-name-link {
        font-weight: 700; font-size: 14px; color: #2E3230; text-decoration: none;
    }
    .pet-name-link:hover { color: #4A7C59; }
    .pet-sub { font-size: 12px; color: #6B6358; }

    .species-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 9999px;
        font-size: 11px; font-weight: 700;
    }
    .branch-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 9999px;
        background: #F0ECE4; color: #4A4E4A;
        font-size: 11px; font-weight: 600; margin: 2px 4px 2px 0;
    }
    .branch-chip.mine { background: #C8E8D0; color: #2A6038; }
    .go-btn {
        width: 32px; height: 32px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        color: #6B6358; text-decoration: none; transition: all 0.15s;
    }
    .pets-table tbody tr:hover .go-btn { background: #F0ECE4; color: #4A7C59; }

    /* ---------- Empty ---------- */
    .empty-state { text-align: center; padding: 64px 20px; }
    .empty-icon {
        width: 96px; height: 96px; border-radius: 50%;
        background: #F0ECE4; color: #78A886;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 40px; margin-bottom: 20px;
    }
    .empty-state h4 {
        font-family: 'Literata', serif; font-size: 20px; font-weight: 700;
        color: #2E3230; margin-bottom: 8px;
    }
    .empty-state p { font-family: 'Nunito Sans', sans-serif; color: #6B6358; margin: 0 0 16px; }

    /* ---------- Pagination ---------- */
    .pagination-wrap {
        padding: 20px; border-top: 1px solid #EAE6DE; background: #FAF6F0;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .pagination-info { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; }
    .pagination-wrap .pagination { margin-bottom: 0; }
    .pagination-wrap .page-item.active .page-link {
        background-color: #4A7C59; border-color: #4A7C59; color: #FFFFFF;
    }
    .pagination-wrap .page-link { color: #4A7C59; border-radius: 8px; margin: 0 2px; border-color: #EAE6DE; }

    @media (max-width: 991px) { .pet-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .pet-stats { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
            Pets
        </h2>
        <p class="pets-subtitle">
            <strong style="color: #2E3230;">{{ number_format($totalCount) }}</strong>
            registered {{ \Illuminate\Support\Str::plural('pet', $totalCount) }}
        </p>
        <div class="branch-note">
            <i class="fas fa-location-dot"></i>
            @if($branch)
                Showing all pets &middot; branch tags highlight visits to {{ $branch }}
            @else
                Showing all pets across all branches
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.pets.export', request()->query()) }}" target="_blank"
           class="filter-tab" style="background: #4A7C59; color: #FFFFFF; font-weight: 700; padding: 10px 20px; text-decoration: none;"
           onmouseover="this.style.background='#3d6b4a'" onmouseout="this.style.background='#4A7C59'">
            <i class="fas fa-print me-1"></i> Export / Print Report
        </a>
    </div>
</div>

{{-- Quick Stats --}}
@php
    $statCards = [
        ['Total Pets', $speciesCounts['all'],   'fa-paw',  '#4A7C59', '#FFFFFF'],
        ['Dogs',       $speciesCounts['dog'],   'fa-dog',  '#C8E8D0', '#2A6038'],
        ['Cats',       $speciesCounts['cat'],   'fa-cat',  '#F8E0A8', '#705C30'],
        ['Other Species', $speciesCounts['bird'] + $speciesCounts['rabbit'] + $speciesCounts['other'],
                                                  'fa-dove', '#EAE6DE', '#4A4E4A'],
    ];
@endphp
<div class="pet-stats">
    @foreach($statCards as [$label, $value, $icon, $bg, $fg])
        <div class="pet-stat">
            <div class="pet-stat-icon" style="background: {{ $bg }}; color: {{ $fg }};">
                <i class="fas {{ $icon }}"></i>
            </div>
            <div>
                <div class="pet-stat-value">{{ number_format($value) }}</div>
                <div class="pet-stat-label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="filter-panel">
    <div class="filter-tabs">
        @foreach($tabs as $key => [$label, $icon])
            <a href="{{ $tabUrl($key) }}"
               class="filter-tab {{ $filters['species'] === $key || ($key === 'all' && !isset($tabs[$filters['species']])) ? 'active' : '' }}">
                <i class="fas {{ $icon }}"></i> {{ $label }}
                <span class="count">{{ $speciesCounts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.pets.index') }}" class="search-row">
        @if($filters['species'] !== 'all' && isset($tabs[$filters['species']]))
            <input type="hidden" name="species" value="{{ $filters['species'] }}">
        @endif
        <div class="input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="{{ $filters['search'] }}"
                   placeholder="Search by pet name, owner, or breed...">
        </div>
        <select name="sort" onchange="this.form.submit()" aria-label="Sort pets">
            <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
            <option value="name"   @selected($filters['sort'] === 'name')>Name A–Z</option>
            <option value="owner"  @selected($filters['sort'] === 'owner')>Owner A–Z</option>
        </select>
        <button type="submit" class="filter-tab active" style="padding: 10px 24px;">Search</button>
        @if($hasFilters)
            <a href="{{ route('admin.pets.index') }}" class="clear-link">
                <i class="fas fa-xmark me-1"></i> Clear filters
            </a>
        @endif
    </form>
</div>

{{-- Pets List --}}
<div class="pets-card">
    @if($pets->isEmpty())
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-paw"></i></div>
            <h4>No pets found</h4>
            @if($hasFilters)
                <p>Try adjusting your search or filters.</p>
                <a href="{{ route('admin.pets.index') }}" class="filter-tab active">Clear filters</a>
            @else
                <p>No pets have been registered yet. They will appear here once owners add them in the app.</p>
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="pets-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAll" title="Select All" style="cursor: pointer; width: 16px; height: 16px; accent-color: #4A7C59;">
                        </th>
                        <th>Pet</th>
                        <th>Species / Breed</th>
                        <th>Health Status</th>
                        <th>Owner</th>
                        <th>Age / Gender</th>
                        <th>Seen At</th>
                        <th>Last Visit</th>
                        <th style="text-align: right;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pets as $pet)
                        @php
                            [$pillBg, $pillFg, $pillIcon, $avBg, $avFg] = $styleFor($pet->species);
                            $seenAt = $pet->appointments->pluck('branch')->filter()->unique()->values();
                            $avatarSrc = $pet->avatar_url
                                ? (\Illuminate\Support\Str::startsWith($pet->avatar_url, ['http://', 'https://', '//', 'data:image/'])
                                    ? $pet->avatar_url
                                    : asset('storage/' . ltrim($pet->avatar_url, '/')))
                                : null;
                        @endphp
                        <tr onclick="window.location='{{ route('admin.pets.show', $pet->id) }}'">
                            <td style="width: 40px; text-align: center;" onclick="event.stopPropagation()">
                                <input type="checkbox" name="selected_ids[]" value="{{ $pet->id }}" class="row-checkbox" style="cursor: pointer; width: 16px; height: 16px; accent-color: #4A7C59;">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="pet-avatar" style="background: {{ $avBg }}; color: {{ $avFg }};">
                                        {{ strtoupper(mb_substr($pet->name, 0, 1)) }}
                                        @if($avatarSrc)
                                            <img src="{{ $avatarSrc }}" alt="{{ $pet->name }}" loading="lazy"
                                                 onerror="this.remove()">
                                        @endif
                                    </span>
                                    <div>
                                        <a href="{{ route('admin.pets.show', $pet->id) }}" class="pet-name-link"
                                           onclick="event.stopPropagation()">{{ $pet->name }}</a>
                                        <div class="pet-sub">#{{ $pet->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="species-pill" style="background: {{ $pillBg }}; color: {{ $pillFg }};">
                                    <i class="fas {{ $pillIcon }}"></i> {{ $pet->species ?: 'Other' }}
                                </span>
                                <div class="pet-sub mt-1">{{ $pet->breed ?: 'Breed not specified' }}</div>
                            </td>
                            @php($healthBadge = $pet->healthBadge())
                            <td>
                                <span class="species-pill" style="background: {{ $healthBadge['bg'] }}; color: {{ $healthBadge['color'] }};">
                                    <i class="fas {{ $healthBadge['icon'] }}"></i> {{ $healthBadge['label'] }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $pet->owner->name ?? 'Unknown' }}</div>
                                <div class="pet-sub">{{ $pet->owner->phone_number ?? $pet->owner->email ?? '' }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    {{ $pet->age !== null ? $pet->age . ' ' . \Illuminate\Support\Str::plural('yr', $pet->age) : '—' }}
                                </div>
                                <div class="pet-sub">
                                    @if($pet->gender)
                                        <i class="fas {{ strtolower($pet->gender) === 'female' ? 'fa-venus' : 'fa-mars' }} me-1"></i>{{ $pet->gender }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </td>
                            <td>
                                @forelse($seenAt as $b)
                                    <span class="branch-chip {{ $branch && $b === $branch ? 'mine' : '' }}">
                                        <i class="fas fa-location-dot"></i>{{ \Illuminate\Support\Str::before($b, ' Branch') }}
                                    </span>
                                @empty
                                    <span class="pet-sub">No appointments</span>
                                @endforelse
                            </td>
                            <td>
                                @if($pet->last_visit)
                                    <div style="font-weight: 600;">{{ \Carbon\Carbon::parse($pet->last_visit)->format('M d, Y') }}</div>
                                    <div class="pet-sub">{{ \Carbon\Carbon::parse($pet->last_visit)->diffForHumans() }}</div>
                                @else
                                    <span class="pet-sub">No visits yet</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.medical-records.pet', $pet->id) }}" class="go-btn"
                                   title="View medical records" onclick="event.stopPropagation()">
                                    <i class="fas fa-file-medical"></i>
                                </a>
                                <a href="{{ route('admin.pets.show', $pet->id) }}" class="go-btn"
                                   title="View pet" onclick="event.stopPropagation()">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <button type="button" class="go-btn text-danger" style="border-color: rgba(184,50,48,0.3); color: #B83230;"
                                        title="Delete pet" onclick="event.stopPropagation();"
                                        data-bs-toggle="modal" data-bs-target="#deletePetModal-{{ $pet->id }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>

                                <!-- Delete Pet Modal -->
                                <div class="modal fade text-start" id="deletePetModal-{{ $pet->id }}" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation();">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content" style="border-radius: 24px; border: none;">
                                            <form method="POST" action="{{ route('admin.pets.destroy', $pet->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                                                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                                                        <i class="fas fa-trash-alt me-2"></i> Delete Pet
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                                                    <p class="mb-2">Are you sure you want to delete <strong>{{ $pet->name }}</strong>?</p>
                                                    <div class="p-3 rounded mt-2" style="background: #FFDAD8; border: 1px solid rgba(184,50,48,0.3); color: #690005; font-size: 13px;">
                                                        <i class="fas fa-triangle-exclamation me-1"></i>
                                                        <strong>Warning:</strong> You are deleting Pet Profiles. This removes the pet from the owner's mobile app and deletes all appointments and medical records.
                                                    </div>
                                                </div>
                                                <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                                                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger rounded-pill px-4" style="background: #BA1A1A; border: none;">
                                                        Delete Pet
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
            <div class="pagination-info">
                Showing {{ $pets->firstItem() }}–{{ $pets->lastItem() }} of {{ $pets->total() }}
            </div>
            {{ $pets->links() }}
        </div>
    @endif
</div>

@include('admin.common._batch_actions', [
    'module' => 'pets',
    'itemName' => 'pet(s)',
    'bulkDeleteRoute' => route('admin.pets.bulk-delete'),
    'exportRoute' => route('admin.pets.export'),
])

@endsection
