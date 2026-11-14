@extends('admin.layouts.app')

@section('title', $record ? 'Edit Medical Record' : 'Add Medical Record')

@section('styles')
    @include('admin.medical-records._styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .form-section-title {
        font-family: 'Literata', serif; font-size: 16px; font-weight: 700; color: #2E3230;
        margin: 8px 0 16px; padding-bottom: 10px; border-bottom: 1px solid #EAE6DE;
        display: flex; align-items: center; gap: 8px;
    }
    .form-section-title i { color: #4A7C59; }
    .form-section-title:not(:first-child) { margin-top: 28px; }
    .f-label {
        display: block; font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700;
        color: #6B6358; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
    }
    .f-label .req { color: #B83230; }
    .f-input, .ts-control {
        width: 100%; padding: 10px 16px; border-radius: 16px !important;
        border: 1px solid rgba(196,200,188,0.6) !important; background: #FAF6F0 !important;
        font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #2E3230;
    }
    .f-input:focus, .ts-wrapper.focus .ts-control {
        outline: none; border-color: #4A7C59 !important; box-shadow: 0 0 0 3px rgba(74,124,89,0.1) !important;
    }
    .f-input.is-invalid, .is-invalid + .ts-wrapper .ts-control { border-color: #B83230 !important; }
    textarea.f-input { resize: vertical; min-height: 96px; }
    .f-help { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358; margin-top: 4px; }
    .f-error { font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #B83230; margin-top: 4px; font-weight: 600; }
    .ts-dropdown { border-radius: 16px; overflow: hidden; font-family: 'Nunito Sans', sans-serif; }
    .ts-dropdown .active { background: #C8E8D0; color: #2A6038; }
    .form-actions { display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap; margin-top: 28px; }
</style>
@endsection

@section('content')

@php
    $val = fn ($field, $default = '') => old($field, $record ? ($record->{$field} instanceof \Carbon\Carbon
        ? $record->{$field}->format('Y-m-d') : $record->{$field}) : $default);
    $fieldError = fn ($f) => $errors->first($f);
@endphp

<a href="{{ $record ? route('admin.medical-records.show', $record->id) : route('admin.medical-records.index') }}" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Cancel and go back
</a>

<div class="mb-4">
    <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
        {{ $record ? 'Edit Medical Record' : 'Add Medical Record' }}
    </h2>
    <p class="mr-subtitle">Fields marked <span style="color: #B83230;">*</span> are required.</p>
</div>

<form method="POST"
      action="{{ $record ? route('admin.medical-records.update', $record->id) : route('admin.medical-records.store') }}">
    @csrf
    @if($record) @method('PUT') @endif

    <div class="mr-card">
        {{-- Visit --}}
        <div class="form-section-title"><i class="fas fa-calendar-check"></i> Visit Details</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="f-label" for="pet_id">Pet <span class="req">*</span></label>
                <select id="pet_id" name="pet_id" class="f-input {{ $fieldError('pet_id') ? 'is-invalid' : '' }}"
                        placeholder="Search by pet or owner name..." required>
                    <option value="">Select a pet...</option>
                    @foreach($pets as $p)
                        <option value="{{ $p->id }}" @selected((string) old('pet_id', $selectedPetId) === (string) $p->id)>
                            {{ $p->name }} ({{ $p->species ?: 'Pet' }}) — {{ $p->owner->name ?? 'Unknown owner' }}
                        </option>
                    @endforeach
                </select>
                @if($fieldError('pet_id')) <div class="f-error">{{ $fieldError('pet_id') }}</div> @endif
            </div>
            <div class="col-md-6">
                <label class="f-label" for="consultation_date">Consultation date <span class="req">*</span></label>
                <input type="date" id="consultation_date" name="consultation_date"
                       class="f-input {{ $fieldError('consultation_date') ? 'is-invalid' : '' }}"
                       value="{{ $val('consultation_date', now()->format('Y-m-d')) }}" required>
                @if($fieldError('consultation_date')) <div class="f-error">{{ $fieldError('consultation_date') }}</div> @endif
            </div>
            <div class="col-md-6">
                <label class="f-label" for="next_visit_date">Next visit date</label>
                <input type="date" id="next_visit_date" name="next_visit_date"
                       class="f-input {{ $fieldError('next_visit_date') ? 'is-invalid' : '' }}"
                       value="{{ $val('next_visit_date') }}">
                <div class="f-help">Optional. Used for follow-ups and vaccine reminders.</div>
                @if($fieldError('next_visit_date')) <div class="f-error">{{ $fieldError('next_visit_date') }}</div> @endif
            </div>
            <div class="col-md-6">
                <label class="f-label" for="veterinarian">Attending Veterinarian</label>
                <input type="text" id="veterinarian" name="veterinarian"
                       class="f-input {{ $fieldError('veterinarian') ? 'is-invalid' : '' }}"
                       value="{{ old('veterinarian', $record->veterinarian ?? '') }}"
                       placeholder="e.g. Dr. Maria Santos, DVM">
                @if($fieldError('veterinarian')) <div class="f-error">{{ $fieldError('veterinarian') }}</div> @endif
            </div>
        </div>

        {{-- Diagnosis & treatment --}}
        <div class="form-section-title"><i class="fas fa-stethoscope"></i> Diagnosis &amp; Treatment</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="f-label" for="diagnosis">Diagnosis</label>
                <textarea id="diagnosis" name="diagnosis" rows="4"
                          class="f-input {{ $fieldError('diagnosis') ? 'is-invalid' : '' }}">{{ $val('diagnosis') }}</textarea>
                @if($fieldError('diagnosis')) <div class="f-error">{{ $fieldError('diagnosis') }}</div> @endif
            </div>
            <div class="col-md-6">
                <label class="f-label" for="treatment">Treatment</label>
                <textarea id="treatment" name="treatment" rows="4"
                          class="f-input {{ $fieldError('treatment') ? 'is-invalid' : '' }}">{{ $val('treatment') }}</textarea>
                @if($fieldError('treatment')) <div class="f-error">{{ $fieldError('treatment') }}</div> @endif
            </div>
        </div>

        {{-- Vaccinations & prescriptions --}}
        <div class="form-section-title"><i class="fas fa-syringe"></i> Vaccinations &amp; Prescriptions</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="f-label" for="vaccinations">Vaccinations</label>
                <textarea id="vaccinations" name="vaccinations" rows="4"
                          class="f-input {{ $fieldError('vaccinations') ? 'is-invalid' : '' }}"
                          placeholder="e.g. Rabies, 5-in-1, Bordetella">{{ $val('vaccinations') }}</textarea>
                <div class="f-help">Comma-separated. Each vaccine appears on the pet's vaccination summary.</div>
                @if($fieldError('vaccinations')) <div class="f-error">{{ $fieldError('vaccinations') }}</div> @endif
            </div>
            <div class="col-md-6">
                <label class="f-label" for="prescriptions">Prescriptions</label>
                <textarea id="prescriptions" name="prescriptions" rows="4"
                          class="f-input {{ $fieldError('prescriptions') ? 'is-invalid' : '' }}">{{ $val('prescriptions') }}</textarea>
                @if($fieldError('prescriptions')) <div class="f-error">{{ $fieldError('prescriptions') }}</div> @endif
            </div>
        </div>

        {{-- Notes --}}
        <div class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</div>
        <textarea id="notes" name="notes" rows="4"
                  class="f-input {{ $fieldError('notes') ? 'is-invalid' : '' }}">{{ $val('notes') }}</textarea>
        @if($fieldError('notes')) <div class="f-error">{{ $fieldError('notes') }}</div> @endif

        <div class="form-actions">
            <a href="{{ $record ? route('admin.medical-records.show', $record->id) : route('admin.medical-records.index') }}"
               class="btn-soft">Cancel</a>
            <button type="submit" class="btn-green" id="saveRecordBtn" onclick="if(this.form.checkValidity()){ this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin me-1\'></i> Saving...'; this.form.submit(); }">
                <i class="fas fa-check"></i> {{ $record ? 'Update Medical Record' : 'Save Medical Record' }}
            </button>
        </div>
    </div>
</form>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new TomSelect('#pet_id', { create: false, maxOptions: null, allowEmptyOption: true });
    });
</script>
@endsection
