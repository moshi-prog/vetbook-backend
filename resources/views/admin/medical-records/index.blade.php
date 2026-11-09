@extends('admin.layouts.app')

@section('title', 'Medical Records')

@section('styles')
    @include('admin.medical-records._styles')
@endsection

@section('content')

@php
    $periods = ['all' => 'All time', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year'];
    $periodUrl = fn ($p) => route('admin.medical-records.index', array_filter([
        'period' => $p === 'all' ? null : $p,
        'search' => $filters['search'],
    ]));
    $statCards = [
        ['Total Records',  $stats['total'],      'fa-file-medical',   '#4A7C59', '#FFFFFF'],
        ['This Month',     $stats['this_month'], 'fa-calendar-check', '#C8E8D0', '#2A6038'],
        ['Upcoming Visits',$stats['upcoming'],   'fa-calendar-day',   '#F8E0A8', '#705C30'],
        ['Pets With Records', $stats['pets'],    'fa-paw',            '#EAE6DE', '#4A4E4A'],
    ];
@endphp

{{-- Header --}}
<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
            Medical Records
        </h2>
        <p class="mr-subtitle">
            <strong style="color: #2E3230;">{{ number_format($stats['total']) }}</strong>
            {{ \Illuminate\Support\Str::plural('record', $stats['total']) }} on file
        </p>
        <div class="branch-note">
            <i class="fas fa-location-dot"></i>
            @if($branch)
                Showing all records &middot; branch tags highlight visits to {{ $branch }}
            @else
                Showing all records across all branches
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.medical-records.export', request()->query()) }}" target="_blank"
           class="btn" style="background: #FAF6F0; color: #4A7C59; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; padding: 10px 18px; border: 1px solid #EAE6DE; border-radius: 9999px; text-decoration: none;"
           onmouseover="this.style.background='#F0ECE4'" onmouseout="this.style.background='#FAF6F0'">
            <i class="fas fa-print me-1"></i> Export / Print Report
        </a>
        <a href="{{ route('admin.medical-records.create') }}" class="btn-green">
            <i class="fas fa-plus"></i> Add Medical Record
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="mr-stats">
    @foreach($statCards as [$label, $value, $icon, $bg, $fg])
        <div class="mr-stat">
            <div class="mr-stat-icon" style="background: {{ $bg }}; color: {{ $fg }};"><i class="fas {{ $icon }}"></i></div>
            <div>
                <div class="mr-stat-value">{{ number_format($value) }}</div>
                <div class="mr-stat-label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="filter-panel">
    <div class="filter-tabs">
        @foreach($periods as $key => $label)
            <a href="{{ $periodUrl($key) }}" class="filter-tab {{ $filters['period'] === $key ? 'active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.medical-records.index') }}" class="search-row">
        @if($filters['period'] !== 'all')
            <input type="hidden" name="period" value="{{ $filters['period'] }}">
        @endif
        <div class="input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="{{ $filters['search'] }}"
                   placeholder="Search by pet, owner, or diagnosis...">
        </div>
        <button type="submit" class="filter-tab active" style="padding: 10px 24px;">Search</button>
        @if($hasFilters)
            <a href="{{ route('admin.medical-records.index') }}" class="clear-link">
                <i class="fas fa-xmark me-1"></i> Clear filters
            </a>
        @endif
    </form>
</div>

{{-- Timeline --}}
@if($records->isEmpty())
    <div class="mr-card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-file-medical"></i></div>
            <h4>{{ $hasFilters ? 'No matching records' : 'No medical records yet' }}</h4>
            @if($hasFilters)
                <p>Try adjusting your search or filters.</p>
                <a href="{{ route('admin.medical-records.index') }}" class="filter-tab active">Clear filters</a>
            @else
                <p>Records added after consultations will appear here as a timeline.</p>
                <a href="{{ route('admin.medical-records.create') }}" class="btn-green">
                    <i class="fas fa-plus"></i> Add the first record
                </a>
            @endif
        </div>
    </div>
@else
    <div class="d-flex align-items-center justify-content-between p-3 mb-3 bg-white rounded-4 border" style="box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
        <label class="d-flex align-items-center gap-2 mb-0" style="cursor: pointer; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; color: #4A4E4A;">
            <input type="checkbox" id="selectAll" style="cursor: pointer; width: 18px; height: 18px; accent-color: #4A7C59;">
            Select All Records on Page
        </label>
        <span class="text-muted small">Check multiple records to perform bulk actions</span>
    </div>

    @include('admin.medical-records._timeline', ['records' => $records, 'showPet' => true, 'branch' => $branch])

    <div class="pagination-wrap">
        <div class="pagination-info">Showing {{ $records->firstItem() }}–{{ $records->lastItem() }} of {{ $records->total() }}</div>
        {{ $records->links() }}
    </div>

    @include('admin.common._batch_actions', [
        'module' => 'medical-records',
        'itemName' => 'medical record(s)',
        'bulkDeleteRoute' => route('admin.medical-records.bulk-delete'),
        'exportRoute' => route('admin.medical-records.export'),
    ])
@endif

@endsection
