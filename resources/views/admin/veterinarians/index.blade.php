@extends('admin.layouts.app')

@section('title', 'Veterinarians')

@section('styles')
<style>
    /* ---------- Header ---------- */
    .vets-subtitle {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #6B6358;
        margin: 0;
    }
    .branch-badge-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 5px 14px;
        border-radius: 9999px;
        background: #C8E8D0;
        color: #2A6038;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }

    /* ---------- Stat Cards ---------- */
    .vet-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .vet-stat {
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46, 50, 48, 0.05);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .vet-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 26px rgba(46, 50, 48, 0.08);
    }
    .vet-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .vet-stat-value {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1;
    }
    .vet-stat-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 600;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 6px;
    }

    /* ---------- Filter Panel ---------- */
    .filter-panel {
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46, 50, 48, 0.05);
        margin-bottom: 24px;
    }
    .filter-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
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
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 11px;
        background: rgba(107, 99, 88, 0.15);
        color: #6B6358;
    }
    .filter-tab.active .count { background: rgba(255, 255, 255, 0.3); color: #FFFFFF; }

    .search-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    .search-row .input-wrap {
        position: relative;
        flex: 1;
        min-width: 240px;
    }
    .search-row .input-wrap i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B6358;
        font-size: 14px;
    }
    .search-row input[type="text"],
    .search-row select {
        padding: 10px 16px;
        border-radius: 16px;
        border: 1px solid rgba(196, 200, 188, 0.4);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
    }
    .search-row input[type="text"] {
        width: 100%;
        padding-left: 40px;
    }
    .search-row select { cursor: pointer; }
    .search-row input:focus, .search-row select:focus {
        outline: none;
        border-color: #4A7C59;
        box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.1);
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

    /* ---------- Add Button ---------- */
    .btn-add-vet {
        background: #4A7C59;
        color: #FFFFFF;
        border: none;
        border-radius: 9999px;
        padding: 10px 22px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(74, 124, 89, 0.25);
        transition: all 0.15s;
        text-decoration: none;
    }
    .btn-add-vet:hover {
        background: #3A6346;
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(74, 124, 89, 0.35);
    }

    /* ---------- Table Card ---------- */
    .vets-card {
        background: #FFFFFF;
        border: 1px solid rgba(196, 200, 188, 0.3);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(46, 50, 48, 0.05);
    }
    .vets-table {
        width: 100%;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        margin: 0;
    }
    .vets-table thead { background: #F5F1EA; }
    .vets-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B6358;
        padding: 16px;
        border-bottom: 1px solid #EAE6DE;
        white-space: nowrap;
    }
    .vets-table td {
        padding: 16px;
        border-bottom: 1px solid #EAE6DE;
        color: #2E3230;
        vertical-align: middle;
    }
    .vets-table tbody tr:hover { background: rgba(245, 241, 234, 0.6); }
    .vets-table tbody tr:last-child td { border-bottom: none; }

    .vet-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #C8E8D0;
        color: #2A6038;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Literata', serif;
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .vet-name {
        font-weight: 700;
        font-size: 14px;
        color: #2E3230;
    }
    .vet-license {
        font-size: 12px;
        color: #6B6358;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Badges */
    .spec-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        background: #F0ECE4;
        color: #2E3230;
        max-width: 240px;
    }
    .branch-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
    }
    .branch-mabini { background: #F8E0A8; color: #705C30; }
    .branch-batangas { background: #D8E6F0; color: #35607E; }
    .branch-all { background: #C8E8D0; color: #2A6038; }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: opacity 0.15s;
    }
    .status-badge:hover { opacity: 0.85; }
    .status-active { background: #C8E8D0; color: #2A6038; }
    .status-inactive { background: #EAE6DE; color: #6B6358; }
    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .status-active .status-dot { background: #2A6038; }
    .status-inactive .status-dot { background: #6B6358; }

    /* Action Buttons */
    .btn-action-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        border: 1px solid rgba(196, 200, 188, 0.4);
        background: #FAF6F0;
        color: #2E3230;
        transition: all 0.15s;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-action-icon:hover {
        background: #F0ECE4;
        color: #4A7C59;
        border-color: #4A7C59;
    }
    .btn-action-delete:hover {
        background: #FFDAD8;
        color: #B83230;
        border-color: #B83230;
    }

    /* Modals */
    .modal-content {
        border-radius: 24px;
        border: 1px solid rgba(196, 200, 188, 0.4);
        box-shadow: 0 16px 40px rgba(46, 50, 48, 0.15);
        background: #FFFFFF;
        font-family: 'Nunito Sans', sans-serif;
    }
    .modal-header {
        border-bottom: 1px solid #EAE6DE;
        padding: 20px 24px;
    }
    .modal-title {
        font-family: 'Literata', serif;
        font-weight: 700;
        font-size: 18px;
        color: #2E3230;
    }
    .modal-body {
        padding: 24px;
    }
    .modal-footer {
        border-top: 1px solid #EAE6DE;
        padding: 16px 24px;
    }
    .f-label {
        font-size: 12px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: block;
    }
    .f-input {
        width: 100%;
        padding: 10px 14px;
        border-radius: 14px;
        border: 1px solid rgba(196, 200, 188, 0.6);
        background: #FAF6F0;
        font-size: 14px;
        color: #2E3230;
    }
    .f-input:focus {
        outline: none;
        border-color: #4A7C59;
        box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.1);
    }

    /* Empty state */
    .empty-state { text-align: center; padding: 64px 20px; }
    .empty-icon {
        width: 80px; height: 80px; border-radius: 50%;
        background: #F0ECE4; color: #78A886;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 34px; margin-bottom: 16px;
    }
    .empty-state h4 {
        font-family: 'Literata', serif; font-size: 20px; font-weight: 700;
        color: #2E3230; margin-bottom: 6px;
    }
    .empty-state p { font-family: 'Nunito Sans', sans-serif; color: #6B6358; margin: 0 0 16px; }

    @media (max-width: 991px) { .vet-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .vet-stats { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')

{{-- Header Row --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
            Veterinarians
        </h2>
        <p class="vets-subtitle">Manage veterinary doctors, licenses, specializations, and clinic branch assignments.</p>
        @if($adminBranch)
            <div class="branch-badge-note">
                <i class="fa-solid fa-code-branch"></i>
                Scoped to {{ $adminBranch }}
            </div>
        @else
            <div class="branch-badge-note" style="background: #F0ECE4; color: #4A4E4A;">
                <i class="fa-solid fa-hospital-user"></i>
                Super Admin Scope &mdash; All Clinic Branches
            </div>
        @endif
    </div>
    <div>
        <button type="button" class="btn-add-vet" data-bs-toggle="modal" data-bs-target="#addVetModal">
            <i class="fa-solid fa-user-plus"></i>
            Add Veterinarian
        </button>
    </div>
</div>

{{-- Stat Cards --}}
<div class="vet-stats">
    <div class="vet-stat">
        <div class="vet-stat-icon" style="background: #C8E8D0; color: #2A6038;">
            <i class="fa-solid fa-user-doctor"></i>
        </div>
        <div>
            <div class="vet-stat-value">{{ $stats['total'] }}</div>
            <div class="vet-stat-label">Total Doctors</div>
        </div>
    </div>
    <div class="vet-stat">
        <div class="vet-stat-icon" style="background: #D8F0DE; color: #4A7C59;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="vet-stat-value">{{ $stats['active'] }}</div>
            <div class="vet-stat-label">Active Doctors</div>
        </div>
    </div>
    <div class="vet-stat">
        <div class="vet-stat-icon" style="background: #F8E0A8; color: #705C30;">
            <i class="fa-solid fa-location-dot"></i>
        </div>
        <div>
            <div class="vet-stat-value">{{ $stats['mabini'] }}</div>
            <div class="vet-stat-label">Mabini Branch</div>
        </div>
    </div>
    <div class="vet-stat">
        <div class="vet-stat-icon" style="background: #D8E6F0; color: #35607E;">
            <i class="fa-solid fa-hospital"></i>
        </div>
        <div>
            <div class="vet-stat-value">{{ $stats['batangas'] }}</div>
            <div class="vet-stat-label">Batangas City</div>
        </div>
    </div>
</div>

{{-- Filter & Search Panel --}}
<div class="filter-panel">
    {{-- Status Tabs --}}
    <div class="filter-tabs">
        @php
            $currStatus = request('status', 'all');
            $currBranch = request('branch', 'all');
            $currSearch = request('search', '');
            
            $tabLink = function($status) use ($currBranch, $currSearch) {
                return route('admin.veterinarians.index', array_filter([
                    'status' => $status !== 'all' ? $status : null,
                    'branch' => $currBranch !== 'all' ? $currBranch : null,
                    'search' => $currSearch ?: null,
                ]));
            };
        @endphp
        <a href="{{ $tabLink('all') }}" class="filter-tab {{ $currStatus === 'all' ? 'active' : '' }}">
            <i class="fa-solid fa-stethoscope"></i> All Doctors
            <span class="count">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ $tabLink('active') }}" class="filter-tab {{ $currStatus === 'active' ? 'active' : '' }}">
            <i class="fa-solid fa-circle-check"></i> Active
            <span class="count">{{ $stats['active'] }}</span>
        </a>
        <a href="{{ $tabLink('inactive') }}" class="filter-tab {{ $currStatus === 'inactive' ? 'active' : '' }}">
            <i class="fa-solid fa-circle-xmark"></i> Inactive
            <span class="count">{{ $stats['inactive'] }}</span>
        </a>
    </div>

    {{-- Search and Branch Row --}}
    <form method="GET" action="{{ route('admin.veterinarians.index') }}" class="search-row">
        @if(request('status') && request('status') !== 'all')
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search doctor by name, license number, specialization, email...">
        </div>

        @if(!$adminBranch)
            <select name="branch" onchange="this.form.submit()">
                <option value="all" @selected(request('branch') === 'all' || !request('branch'))>All Branches (All Doctors)</option>
                <option value="Mabini Branch" @selected(request('branch') === 'Mabini Branch')>Mabini Branch</option>
                <option value="Batangas City Branch" @selected(request('branch') === 'Batangas City Branch')>Batangas City Branch</option>
                <option value="all_branches_only" @selected(request('branch') === 'all_branches_only')>Roving / Both Branches</option>
            </select>
        @endif

        <button type="submit" class="btn-add-vet" style="padding: 10px 18px;">
            <i class="fa-solid fa-filter"></i> Apply Filter
        </button>

        @if(request()->hasAny(['search', 'branch', 'status']))
            <a href="{{ route('admin.veterinarians.index') }}" class="clear-link">
                <i class="fa-solid fa-xmark"></i> Clear Filters
            </a>
        @endif
    </form>
</div>

{{-- Veterinarians Table --}}
<div class="vets-card">
    @if($veterinarians->count() === 0)
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <h4>No Veterinarians Found</h4>
            <p>No veterinary profiles match your selected search criteria or branch scope.</p>
            <button type="button" class="btn-add-vet" data-bs-toggle="modal" data-bs-target="#addVetModal">
                <i class="fa-solid fa-user-plus"></i> Add New Veterinarian
            </button>
        </div>
    @else
        <div class="table-responsive">
            <table class="vets-table">
                <thead>
                    <tr>
                        <th>Veterinarian</th>
                        <th>Specialization</th>
                        <th>Branch</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($veterinarians as $vet)
                        <tr>
                            {{-- Name & License --}}
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="vet-avatar">
                                        {{ strtoupper(substr($vet->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="vet-name">{{ $vet->name }}</div>
                                        <div class="vet-license">
                                            <i class="fa-solid fa-id-card" style="color: #78A886;"></i>
                                            {{ $vet->license_number ? 'Lic. #' . $vet->license_number : 'License on file' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Specialization --}}
                            <td>
                                @if($vet->specialization)
                                    <span class="spec-badge text-truncate" title="{{ $vet->specialization }}">
                                        <i class="fa-solid fa-certificate" style="color: #4A7C59;"></i>
                                        {{ $vet->specialization }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 12px;">General Practice</span>
                                @endif
                            </td>

                            {{-- Branch --}}
                            <td>
                                @if($vet->branch === 'Mabini Branch')
                                    <span class="branch-badge branch-mabini">
                                        <i class="fa-solid fa-location-dot"></i> Mabini Branch
                                    </span>
                                @elseif($vet->branch === 'Batangas City Branch')
                                    <span class="branch-badge branch-batangas">
                                        <i class="fa-solid fa-location-dot"></i> Batangas City
                                    </span>
                                @else
                                    <span class="branch-badge branch-all">
                                        <i class="fa-solid fa-arrows-split-up-and-left"></i> All Branches (Roving)
                                    </span>
                                @endif
                            </td>

                            {{-- Contact --}}
                            <td>
                                <div style="font-size: 12px; display: flex; flex-direction: column; gap: 2px;">
                                    @if($vet->phone)
                                        <div><i class="fa-solid fa-phone me-1 text-muted"></i>{{ $vet->phone }}</div>
                                    @endif
                                    @if($vet->email)
                                        <div class="text-muted"><i class="fa-solid fa-envelope me-1 text-muted"></i>{{ $vet->email }}</div>
                                    @endif
                                    @if(!$vet->phone && !$vet->email)
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Status toggle form --}}
                            <td>
                                <form method="POST" action="{{ route('admin.veterinarians.toggle-status', $vet->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="status-badge {{ $vet->is_active ? 'status-active' : 'status-inactive' }}"
                                            title="Click to toggle active/inactive status">
                                        <span class="status-dot"></span>
                                        {{ $vet->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>

                            {{-- Actions --}}
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <button type="button"
                                            class="btn-action-icon edit-vet-btn"
                                            title="Edit Veterinarian"
                                            data-id="{{ $vet->id }}"
                                            data-name="{{ $vet->name }}"
                                            data-license="{{ $vet->license_number }}"
                                            data-specialization="{{ $vet->specialization }}"
                                            data-phone="{{ $vet->phone }}"
                                            data-email="{{ $vet->email }}"
                                            data-branch="{{ $vet->branch ?? 'All Branches' }}"
                                            data-active="{{ $vet->is_active ? '1' : '0' }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button"
                                            class="btn-action-icon btn-action-delete delete-vet-btn"
                                            title="Delete Veterinarian"
                                            data-id="{{ $vet->id }}"
                                            data-name="{{ $vet->name }}">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($veterinarians->hasPages())
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Showing {{ $veterinarians->firstItem() }} to {{ $veterinarians->lastItem() }} of {{ $veterinarians->total() }} veterinarians
                </div>
                <div>
                    {{ $veterinarians->links() }}
                </div>
            </div>
        @endif
    @endif
</div>

{{-- ─── Add Veterinarian Modal ─── --}}
<div class="modal fade" id="addVetModal" tabindex="-1" aria-labelledby="addVetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.veterinarians.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addVetModalLabel">
                        <i class="fa-solid fa-user-doctor me-2" style="color: #4A7C59;"></i>
                        Add New Veterinarian
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="f-label" for="add_name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_name" name="name" class="f-input" placeholder="e.g. Dr. Juan Reyes, DVM" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="f-label" for="add_license">License Number</label>
                            <input type="text" id="add_license" name="license_number" class="f-input" placeholder="e.g. PRC-0089214">
                        </div>
                        <div class="col-md-6">
                            <label class="f-label" for="add_branch">Branch Assignment</label>
                            @if($adminBranch)
                                <input type="text" class="f-input" value="{{ $adminBranch }}" disabled>
                                <input type="hidden" name="branch" value="{{ $adminBranch }}">
                            @else
                                <select id="add_branch" name="branch" class="f-input">
                                    <option value="All Branches">All Branches (Roving Doctor)</option>
                                    <option value="Mabini Branch">Mabini Branch</option>
                                    <option value="Batangas City Branch">Batangas City Branch</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="f-label" for="add_spec">Specialization / Expertise</label>
                        <input type="text" id="add_spec" name="specialization" class="f-input" placeholder="e.g. Small Animal Surgery & Dentistry">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="f-label" for="add_phone">Phone Number</label>
                            <input type="text" id="add_phone" name="phone" class="f-input" placeholder="0917-123-4567">
                        </div>
                        <div class="col-md-6">
                            <label class="f-label" for="add_email">Email Address</label>
                            <input type="email" id="add_email" name="email" class="f-input" placeholder="vet@bkpetaholic.com">
                        </div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="add_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label ms-2" for="add_is_active" style="font-weight: 600; color: #2E3230; font-size: 13px;">
                            Active and available for clinic appointments
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add-vet">
                        <i class="fa-solid fa-check"></i> Save Veterinarian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Edit Veterinarian Modal ─── --}}
<div class="modal fade" id="editVetModal" tabindex="-1" aria-labelledby="editVetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editVetForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editVetModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2" style="color: #4A7C59;"></i>
                        Edit Veterinarian Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="f-label" for="edit_name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_name" name="name" class="f-input" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="f-label" for="edit_license">License Number</label>
                            <input type="text" id="edit_license" name="license_number" class="f-input">
                        </div>
                        <div class="col-md-6">
                            <label class="f-label" for="edit_branch">Branch Assignment</label>
                            @if($adminBranch)
                                <input type="text" class="f-input" value="{{ $adminBranch }}" disabled>
                                <input type="hidden" name="branch" value="{{ $adminBranch }}">
                            @else
                                <select id="edit_branch" name="branch" class="f-input">
                                    <option value="All Branches">All Branches (Roving Doctor)</option>
                                    <option value="Mabini Branch">Mabini Branch</option>
                                    <option value="Batangas City Branch">Batangas City Branch</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="f-label" for="edit_spec">Specialization / Expertise</label>
                        <input type="text" id="edit_spec" name="specialization" class="f-input">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="f-label" for="edit_phone">Phone Number</label>
                            <input type="text" id="edit_phone" name="phone" class="f-input">
                        </div>
                        <div class="col-md-6">
                            <label class="f-label" for="edit_email">Email Address</label>
                            <input type="email" id="edit_email" name="email" class="f-input">
                        </div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label ms-2" for="edit_is_active" style="font-weight: 600; color: #2E3230; font-size: 13px;">
                            Active and available for clinic appointments
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add-vet">
                        <i class="fa-solid fa-check"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Delete Veterinarian Modal ─── --}}
<div class="modal fade" id="deleteVetModal" tabindex="-1" aria-labelledby="deleteVetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="deleteVetForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteVetModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" style="font-size: 14px; color: #2E3230;">
                        Are you sure you want to delete <strong id="deleteVetName"></strong>?
                    </p>
                    <p class="text-muted mb-0" style="font-size: 12px;">
                        This action will remove the veterinarian profile from active doctor listings.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" style="background: #B83230; border: none;">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete Veterinarian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit Modal populate
    const editModal = new bootstrap.Modal(document.getElementById('editVetModal'));
    const editForm = document.getElementById('editVetForm');
    const editName = document.getElementById('edit_name');
    const editLicense = document.getElementById('edit_license');
    const editSpec = document.getElementById('edit_spec');
    const editPhone = document.getElementById('edit_phone');
    const editEmail = document.getElementById('edit_email');
    const editBranch = document.getElementById('edit_branch');
    const editActive = document.getElementById('edit_is_active');

    document.querySelectorAll('.edit-vet-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            editForm.action = '{{ url("admin/veterinarians") }}/' + id;
            editName.value = this.dataset.name || '';
            editLicense.value = this.dataset.license || '';
            editSpec.value = this.dataset.specialization || '';
            editPhone.value = this.dataset.phone || '';
            editEmail.value = this.dataset.email || '';
            if (editBranch) {
                editBranch.value = this.dataset.branch || 'All Branches';
            }
            editActive.checked = this.dataset.active === '1';

            editModal.show();
        });
    });

    // Delete Modal populate
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteVetModal'));
    const deleteForm = document.getElementById('deleteVetForm');
    const deleteNameSpan = document.getElementById('deleteVetName');

    document.querySelectorAll('.delete-vet-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            deleteForm.action = '{{ url("admin/veterinarians") }}/' + id;
            deleteNameSpan.textContent = name;
            deleteModal.show();
        });
    });
});
</script>
@endsection

