@extends('admin.layouts.app')

@section('title', 'SMS Notifications')

@section('styles')
<style>
    .sms-subtitle { font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358; margin: 0; }
    .provider-badge {
        display: inline-flex; align-items: center; gap: 10px; padding: 9px 18px; border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700;
        background: #C8E8D0; color: #2A6038;
    }
    .provider-badge.demo { background: #F8E0A8; color: #705C30; }
    .provider-dot { width: 10px; height: 10px; border-radius: 50%; background: #4A7C59; flex-shrink: 0; }
    .provider-badge.demo .provider-dot { background: #D9A93F; animation: pulse-dot 2s infinite; }
    @keyframes pulse-dot {
        0% { box-shadow: 0 0 0 0 rgba(217,169,63,0.5); }
        70% { box-shadow: 0 0 0 8px rgba(217,169,63,0); }
        100% { box-shadow: 0 0 0 0 rgba(217,169,63,0); }
    }

    /* Stats */
    .sms-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .sms-stat {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); display: flex; align-items: center; gap: 16px;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .sms-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(46,50,48,0.08); }
    .sms-stat-icon {
        width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center;
        justify-content: center; font-size: 20px; flex-shrink: 0;
    }
    .sms-stat-value { font-family: 'Literata', serif; font-size: 28px; font-weight: 700; color: #2E3230; line-height: 1; }
    .sms-stat-label {
        font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 600; color: #6B6358;
        text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;
    }
    .sms-stat-sub { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; margin-top: 2px; }
    @media (max-width: 991px) { .sms-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .sms-stats { grid-template-columns: 1fr; } }

    /* Buttons */
    .btn-sms {
        display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif; font-size: 14px; font-weight: 700; border: none;
        cursor: pointer; transition: all 0.15s; text-decoration: none;
    }
    .btn-sms.primary { background: #4A7C59; color: #FFFFFF; }
    .btn-sms.primary:hover { background: #3E6A4B; color: #FFFFFF; }
    .btn-sms.secondary { background: #F5F1EA; color: #2E3230; border: 1px solid #EAE6DE; }
    .btn-sms.secondary:hover { background: #EAE6DE; }

    /* Filters */
    .filter-panel {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px; padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); margin-bottom: 24px;
    }
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

    /* Table card */
    .sms-card {
        background: #FFFFFF; border: 1px solid rgba(196,200,188,0.3); border-radius: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05); margin-bottom: 24px; overflow: hidden;
    }
    .sms-card-head { padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid #EAE6DE; }
    .sms-card-head h3 { font-family: 'Literata', serif; font-size: 20px; font-weight: 700; color: #2E3230; margin: 0; }
    .sms-table { width: 100%; border-collapse: collapse; }
    .sms-table th {
        background: #F5F1EA; padding: 12px 20px; text-align: left; font-family: 'Nunito Sans', sans-serif;
        font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase; letter-spacing: 0.6px; white-space: nowrap;
    }
    .sms-table td {
        padding: 16px 20px; border-top: 1px solid #EAE6DE; font-family: 'Nunito Sans', sans-serif;
        font-size: 13px; color: #2E3230; vertical-align: middle;
    }
    .sms-table tbody tr { transition: background 0.15s; }
    .sms-table tbody tr:hover { background: #FAF6F0; }
    .cell-date { white-space: nowrap; font-weight: 600; }
    .cell-phone { font-weight: 700; }
    .cell-owner { font-size: 12px; color: #6B6358; }
    .cell-msg { max-width: 340px; color: #4A4E4A; cursor: help; }

    .pill {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif; font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .pill-confirmation { background: #C8E8D0; color: #2A6038; }
    .pill-rescheduled  { background: #D6E4FF; color: #234E8C; }
    .pill-completed    { background: #E2D9F3; color: #533885; }
    .pill-reminder     { background: #F8E0A8; color: #705C30; }
    .pill-vaccination  { background: #DCE6EE; color: #3E5668; }
    .pill-cancellation { background: #FFDAD8; color: #B83230; }
    .pill-delivered { background: #C8E8D0; color: #2A6038; }
    .pill-failed    { background: #FFDAD8; color: #B83230; }
    .pill-pending   { background: #F8E0A8; color: #705C30; }
    .pill-general, .pill-test { background: #EAE6DE; color: #4A4E4A; }
    .pill i.dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block; }

    .icon-btn {
        width: 34px; height: 34px; border-radius: 50%; border: none; background: #F5F1EA; color: #4A7C59;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s;
    }
    .icon-btn:hover { background: #4A7C59; color: #FFFFFF; }

    .table-footer {
        padding: 16px 24px; border-top: 1px solid #EAE6DE; display: flex; justify-content: space-between;
        align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .pagination-info { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; }
    .table-footer .pagination { margin-bottom: 0; }
    .table-footer .page-item.active .page-link { background-color: #4A7C59; border-color: #4A7C59; color: #FFFFFF; }
    .table-footer .page-link { color: #4A7C59; border-radius: 8px; margin: 0 2px; border-color: #EAE6DE; }
    .table-footer .page-item.disabled .page-link { color: #A8A298; }

    .empty-state { text-align: center; padding: 64px 20px; }
    .empty-icon {
        width: 96px; height: 96px; border-radius: 50%; background: #F0ECE4; color: #78A886;
        display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px;
    }
    .empty-state h4 { font-family: 'Literata', serif; font-size: 20px; font-weight: 700; color: #2E3230; margin-bottom: 8px; }
    .empty-state p { font-family: 'Nunito Sans', sans-serif; color: #6B6358; margin: 0 0 16px; }

    /* Templates */
    .tpl-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; padding: 24px; }
    @media (max-width: 1399px) { .tpl-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 767px)  { .tpl-grid { grid-template-columns: 1fr; } }
    .tpl-card {
        background: #F5F1EA; border-radius: 20px; padding: 18px; display: flex; flex-direction: column;
        border: 1px solid transparent; transition: all 0.15s;
    }
    .tpl-card:hover { border-color: #C8E8D0; background: #F0ECE4; transform: translateY(-2px); }
    .tpl-name { font-family: 'Literata', serif; font-size: 16px; font-weight: 700; color: #2E3230; margin: 10px 0 8px; }
    .tpl-body {
        font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #4A4E4A; line-height: 1.5; flex: 1;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .tpl-foot { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; }
    .tpl-count { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; }
    .tpl-edit {
        padding: 6px 16px; border-radius: 9999px; background: #FFFFFF; color: #4A7C59; border: 1px solid #C8E8D0;
        font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.15s;
    }
    .tpl-edit:hover { background: #4A7C59; color: #FFFFFF; }

    /* Modals */
    .sms-modal .modal-content { border-radius: 24px; border: none; box-shadow: 0 20px 50px rgba(46,50,48,0.2); }
    .sms-modal .modal-header { border-bottom: 1px solid #EAE6DE; padding: 20px 24px; }
    .sms-modal .modal-title { font-family: 'Literata', serif; font-weight: 700; color: #2E3230; }
    .sms-modal .modal-body { padding: 24px; font-family: 'Nunito Sans', sans-serif; }
    .sms-modal .modal-footer { border-top: 1px solid #EAE6DE; padding: 16px 24px; }
    .sms-modal label { font-size: 12px; font-weight: 700; color: #6B6358; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .sms-modal .form-control, .sms-modal .form-select {
        border-radius: 16px; border: 1px solid rgba(196,200,188,0.4); background: #FAF6F0; padding: 10px 16px; font-size: 14px;
    }
    .sms-modal .form-control:focus, .sms-modal .form-select:focus { border-color: #4A7C59; box-shadow: 0 0 0 3px rgba(74,124,89,0.1); }
    .demo-note {
        display: flex; gap: 10px; align-items: flex-start; background: #FBF0D6; color: #705C30; border-radius: 16px;
        padding: 12px 16px; font-size: 13px; margin-bottom: 18px;
    }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #EAE6DE; font-size: 14px; gap: 16px; }
    .detail-row span:first-child { color: #6B6358; font-weight: 600; }
    .detail-msg { background: #F5F1EA; border-radius: 16px; padding: 14px 16px; margin-top: 16px; font-size: 14px; color: #2E3230; line-height: 1.5; }
</style>
@endsection

@section('content')

@php
    $typeLabels = [
        'confirmation' => 'Confirmation',
        'rescheduled'  => 'Rescheduled',
        'completed'    => 'Completed',
        'reminder'     => 'Reminder',
        'vaccination'  => 'Vaccination',
        'cancellation' => 'Cancellation',
        'test'         => 'Test',
        'general'      => 'General',
    ];
    $statusLabels = ['delivered' => 'Delivered', 'failed' => 'Failed', 'pending' => 'Pending'];
    $statCards = [
        ['Total Sent',  number_format($stats['total_sent']), 'All time',                                'fa-paper-plane',        '#4A7C59', '#FFFFFF'],
        ['Delivered',   number_format($stats['delivered']), $stats['delivery_rate'] . '% delivery rate', 'fa-circle-check',       '#C8E8D0', '#2A6038'],
        ['Failed',      number_format($stats['failed']),    'Undelivered',                             'fa-circle-exclamation', '#FFDAD8', '#B83230'],
        ['Pending',     number_format($stats['pending']),   'Queued to send',                          'fa-clock',              '#F8E0A8', '#705C30'],
    ];
    $hasFilters = $filters['search'] !== '' || $filters['type'] !== 'all' || $filters['status'] !== 'all' || $filters['range'] !== 'month';
    $shown = $logs->count();
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">SMS Notifications</h2>
        <p class="sms-subtitle">Automated appointment reminders and confirmations</p>
    </div>
    @if($provider['connected'])
        <div class="provider-badge"><span class="provider-dot"></span> {{ $provider['name'] }} — Connected</div>
    @else
        <div class="provider-badge demo" title="SMS sending is disabled until a IPROG SMS API token is configured.">
            <span class="provider-dot"></span> {{ $provider['name'] }} — Not Connected
        </div>
    @endif
</div>

{{-- Stats --}}
<div class="sms-stats">
    @foreach($statCards as [$label, $value, $sub, $icon, $bg, $fg])
        <div class="sms-stat">
            <div class="sms-stat-icon" style="background: {{ $bg }}; color: {{ $fg }};"><i class="fas {{ $icon }}"></i></div>
            <div>
                <div class="sms-stat-value">{{ $value }}</div>
                <div class="sms-stat-label">{{ $label }}</div>
                <div class="sms-stat-sub">{{ $sub }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.sms.index') }}" class="filter-panel" id="smsFilterForm">
    <div class="search-row">
        <div class="input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by phone number or message content...">
        </div>
        <select name="type" onchange="this.form.submit()" aria-label="Filter by type">
            <option value="all">All types</option>
            <option value="confirmation" @selected($filters['type'] === 'confirmation')>Appointment Confirmation</option>
            <option value="rescheduled" @selected($filters['type'] === 'rescheduled')>Appointment Rescheduled</option>
            <option value="completed" @selected($filters['type'] === 'completed')>Appointment Completed</option>
            <option value="cancellation" @selected($filters['type'] === 'cancellation')>Cancellation</option>
            <option value="reminder" @selected($filters['type'] === 'reminder')>Appointment Reminder</option>
            <option value="vaccination" @selected($filters['type'] === 'vaccination')>Vaccination Alert</option>
        </select>
        <select name="status" onchange="this.form.submit()" aria-label="Filter by status">
            <option value="all">All statuses</option>
            <option value="delivered" @selected($filters['status'] === 'delivered')>Delivered</option>
            <option value="failed" @selected($filters['status'] === 'failed')>Failed</option>
            <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
        </select>
        <select name="range" onchange="this.form.submit()" aria-label="Filter by date range">
            <option value="week" @selected($filters['range'] === 'week')>This week</option>
            <option value="month" @selected($filters['range'] === 'month')>This month</option>
            <option value="30days" @selected($filters['range'] === '30days')>Last 30 days</option>
            <option value="all" @selected($filters['range'] === 'all')>All time</option>
        </select>
        @if($hasFilters)
            <a href="{{ route('admin.sms.index') }}" class="clear-link"><i class="fas fa-xmark me-1"></i>Clear filters</a>
        @endif
    </div>
</form>

{{-- Sent log --}}
<div class="sms-card">
    <div class="sms-card-head">
        <h3>Sent Log</h3>
        <button type="button" class="btn-sms primary" data-bs-toggle="modal" data-bs-target="#composeModal">
            <i class="fas fa-plus"></i> Compose SMS
        </button>
    </div>

    @if($shown === 0)
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-bell"></i></div>
            <h4>No SMS notifications yet</h4>
            <p>{{ $hasFilters ? 'No messages match your filters.' : 'Messages sent to pet owners will appear here.' }}</p>
            @if($hasFilters)
                <a href="{{ route('admin.sms.index') }}" class="btn-sms secondary">Clear filters</a>
            @endif
        </div>
    @else
        <div class="table-responsive">
            <table class="sms-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Recipient</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        @php
                            $logType = $typeLabels[$log->type] ?? ucfirst($log->type);
                            $logStatus = $statusLabels[$log->status] ?? ucfirst($log->status);
                            $ownerName = $log->user->name ?? null;
                        @endphp
                        <tr>
                            <td class="cell-date">{{ $log->created_at->format('M d, g:i A') }}</td>
                            <td>
                                <div class="cell-phone">{{ $log->recipient_phone }}</div>
                                <div class="cell-owner">{{ $ownerName ?? 'Unknown' }}</div>
                            </td>
                            <td><span class="pill pill-{{ $log->type }}">{{ $logType }}</span></td>
                            <td class="cell-msg" title="{{ $log->message }}">{{ \Illuminate\Support\Str::limit($log->message, 60) }}</td>
                            <td><span class="pill pill-{{ $log->status }}"><i class="dot"></i>{{ $logStatus }}</span></td>
                            <td class="text-center">
                                <button type="button" class="icon-btn sms-view" title="View details"
                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                    data-date="{{ $log->created_at->format('M d, Y g:i A') }}" data-phone="{{ $log->recipient_phone }}"
                                    data-owner="{{ $ownerName ?: '—' }}" data-type="{{ $logType }}"
                                    data-status="{{ $logStatus }}" data-message="{{ $log->message }}"
                                    data-error="{{ $log->error_message }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="table-footer">
                <div class="pagination-info">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} messages
                </div>
                {{ $logs->links() }}
            </div>
        @else
            <div class="table-footer">
                <div class="pagination-info">{{ number_format($logs->total()) }} {{ \Illuminate\Support\Str::plural('message', $logs->total()) }}</div>
            </div>
        @endif
    @endif
</div>

{{-- Templates --}}
<div class="sms-card">
    <div class="sms-card-head">
        <h3>SMS Templates</h3>
        <a href="{{ route('admin.settings.index') }}#sms" class="clear-link" style="color:#4A7C59;">
            <i class="fas fa-gear me-1"></i>SMS settings
        </a>
    </div>
    <div class="tpl-grid">
        @foreach($templates as $tpl)
            <div class="tpl-card">
                <div><span class="pill pill-{{ $tpl['type'] }}">{{ $typeLabels[$tpl['type']] }}</span></div>
                <div class="tpl-name">{{ $tpl['name'] }}</div>
                <div class="tpl-body">{{ $tpl['body'] }}</div>
                <div class="tpl-foot">
                    <span class="tpl-count">{{ mb_strlen($tpl['body']) }} chars · {{ (int) ceil(mb_strlen($tpl['body']) / 160) }} SMS</span>
                    <a href="{{ route('admin.settings.index') }}#sms" class="tpl-edit text-decoration-none">Edit</a>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Compose modal --}}
<div class="modal fade sms-modal" id="composeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.sms.compose') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Compose SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="demo-note"><i class="fas fa-circle-info mt-1"></i><span>Sends a real SMS via IPROG SMS. Plain ASCII only, max 160 characters (1 credit).</span></div>
                <div class="mb-3">
                    <label for="composePhone">Recipient phone</label>
                    <input type="text" class="form-control" id="composePhone" name="phone" placeholder="09XXXXXXXXX" required>
                </div>
                <div>
                    <label for="composeMsg">Message</label>
                    <textarea class="form-control" id="composeMsg" name="message" rows="4" maxlength="160" required></textarea>
                    <div class="text-end mt-1" style="font-size:12px;color:#6B6358;"><span id="composeCount">0</span>/160</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sms secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-sms primary"><i class="fas fa-paper-plane"></i> Send SMS</button>
            </div>
        </form>
    </div>
</div>

{{-- Details modal --}}
<div class="modal fade sms-modal" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="detail-row"><span>Sent</span><span id="dDate"></span></div>
                <div class="detail-row"><span>Phone</span><span id="dPhone"></span></div>
                <div class="detail-row"><span>Owner</span><span id="dOwner"></span></div>
                <div class="detail-row"><span>Type</span><span id="dType"></span></div>
                <div class="detail-row"><span>Status</span><span id="dStatus"></span></div>
                <div class="detail-row d-none" id="dErrorRow"><span>Error</span><span id="dError" style="color:#B83230;text-align:right;"></span></div>
                <div class="detail-msg" id="dMessage"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const set = (id, v) => document.getElementById(id).textContent = v;
        document.querySelectorAll('.sms-view').forEach(btn => {
            btn.addEventListener('click', () => {
                const d = btn.dataset;
                set('dDate', d.date); set('dPhone', d.phone); set('dOwner', d.owner);
                set('dType', d.type); set('dStatus', d.status); set('dMessage', d.message);
                document.getElementById('dErrorRow').classList.toggle('d-none', !d.error);
                set('dError', d.error || '');
            });
        });

        const msg = document.getElementById('composeMsg');
        const count = document.getElementById('composeCount');
        const updateCount = () => {
            count.textContent = msg.value.length;
            count.style.color = msg.value.length > 160 ? '#B83230' : '';
        };
        msg.addEventListener('input', updateCount);

        // Debounced search submit
        const search = document.querySelector('#smsFilterForm input[name="search"]');
        search.addEventListener('keydown', e => { if (e.key === 'Enter') e.target.form.submit(); });
    });
</script>
@endsection
