@extends('admin.layouts.app')

@section('title', $pet->name . ' — Records')

@section('styles')
    @include('admin.medical-records._styles')
@endsection

@section('content')

@php
    $statusLabels = ['ok' => 'Up to date', 'soon' => 'Due soon', 'overdue' => 'Overdue'];
    $seenAt = $pet->appointments->pluck('branch')->filter()->unique()->values();
@endphp

<a href="{{ route('admin.medical-records.index') }}" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Back to Medical Records
</a>

{{-- Pet banner --}}
<div class="mr-card" style="background: #F5F1EA;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            @include('admin.medical-records._avatar', ['pet' => $pet, 'size' => 64])
            <div>
                <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin: 0;">{{ $pet->name }}</h2>
                <div class="mr-subtitle">
                    {{ $pet->species ?: 'Pet' }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}
                    @if($pet->age !== null) &middot; {{ $pet->age }} {{ \Illuminate\Support\Str::plural('yr', $pet->age) }} @endif
                    @if($pet->gender) &middot; {{ $pet->gender }} @endif
                    @if($pet->weight) &middot; {{ rtrim(rtrim($pet->weight, '0'), '.') }} kg @endif
                </div>
                <div class="mr-subtitle mt-1">
                    <i class="fas fa-user me-1" style="color: #4A7C59;"></i>
                    {{ $pet->owner->name ?? 'Unknown owner' }}
                    @if($pet->owner?->phone_number) &middot; {{ $pet->owner->phone_number }} @endif
                </div>
                <div class="mt-2">
                    @foreach($seenAt as $b)
                        <span class="branch-chip {{ $branch && $b === $branch ? 'mine' : '' }}">
                            <i class="fas fa-location-dot"></i>{{ \Illuminate\Support\Str::before($b, ' Branch') }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        <a href="{{ route('admin.medical-records.create', ['pet_id' => $pet->id]) }}" class="btn-green">
            <i class="fas fa-plus"></i> Add Medical Record
        </a>
    </div>
    @if($pet->medical_conditions)
        <div class="mt-3 p-3" style="background: #FFFFFF; border-radius: 16px; font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #2E3230;">
            <strong style="color: #705C30;"><i class="fas fa-triangle-exclamation me-1"></i>Known conditions:</strong>
            {{ $pet->medical_conditions }}
        </div>
    @endif
</div>

@if($records->isEmpty())
    <div class="mr-card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-file-medical"></i></div>
            <h4>No records for this pet yet</h4>
            <p>Add the first medical record after {{ $pet->name }}'s consultation.</p>
            <a href="{{ route('admin.medical-records.create', ['pet_id' => $pet->id]) }}" class="btn-green">
                <i class="fas fa-plus"></i> Add Record
            </a>
        </div>
    </div>
@else
    {{-- Vaccination summary --}}
    <div class="mr-card">
        <h3><i class="fas fa-syringe me-2" style="color: #4A7C59;"></i>Vaccinations</h3>
        @if(count($vaccinations))
            @foreach($vaccinations as $v)
                <span class="vax-pill {{ $v['status'] }}"
                      title="{{ $statusLabels[$v['status']] }}{{ $v['next_visit'] ? ' · follow-up ' . $v['next_visit']->format('M d, Y') : '' }}">
                    <i class="fas {{ $v['status'] === 'overdue' ? 'fa-circle-exclamation' : ($v['status'] === 'soon' ? 'fa-clock' : 'fa-circle-check') }}"></i>
                    {{ $v['name'] }}
                    <small>{{ $v['last_given']->format('M d, Y') }}</small>
                </span>
            @endforeach
            <div class="mr-subtitle mt-2" style="font-size: 12px;">
                <span class="vax-pill ok" style="padding: 2px 10px; font-size: 11px;">Up to date</span>
                <span class="vax-pill soon" style="padding: 2px 10px; font-size: 11px;">Follow-up within 30 days</span>
                <span class="vax-pill overdue" style="padding: 2px 10px; font-size: 11px;">Follow-up overdue</span>
                Dates show when each vaccine was last given.
            </div>
        @else
            <p class="mr-subtitle mb-0">No vaccinations recorded yet.</p>
        @endif
    </div>

    {{-- Timeline --}}
    <div class="tl-month" style="margin-top: 0;">
        {{ $records->count() }} {{ \Illuminate\Support\Str::plural('record', $records->count()) }}
    </div>
    @include('admin.medical-records._timeline', ['records' => $records, 'showPet' => false, 'branch' => $branch])
@endif

@endsection
