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
    .med-quick-pick-wrap {
        background: #FAF6F0; border: 1px solid #EAE6DE; border-radius: 14px;
        padding: 10px 12px; margin-bottom: 10px;
    }
    .med-chip-btn {
        display: inline-flex; align-items: center; gap: 4px;
        background: #E1F3E6; color: #2A6038; border: 1px solid #B7DEC2;
        border-radius: 9999px; font-family: 'Nunito Sans', sans-serif;
        font-size: 12px; font-weight: 700; padding: 4px 11px; cursor: pointer;
        transition: all 0.15s ease;
    }
    .med-chip-btn:hover:not(:disabled) {
        background: #C8E8D0; border-color: #4A7C59;
    }
    .med-chip-btn:disabled {
        background: #F0ECE4; color: #8C857B; border-color: #DFDAD0; cursor: not-allowed;
    }
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
      enctype="multipart/form-data"
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
                @if(isset($medicines) && $medicines->isNotEmpty())
                    <div class="med-quick-pick-wrap">
                        <div style="font-family: 'Nunito Sans', sans-serif; font-size: 11px; font-weight: 700; color: #2A6038; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px;">
                            <i class="fas fa-pills me-1"></i> Quick-Pick from Medicine Stock
                        </div>
                        <div class="d-flex flex-wrap gap-1" id="medicineQuickPickBar">
                            @foreach($medicines as $med)
                                @php
                                    $stock = (int) ($med->stock ?? 0);
                                    $unit = $med->unit ?: 'units';
                                @endphp
                                <button type="button"
                                        class="med-chip-btn"
                                        data-med-id="{{ $med->id }}"
                                        data-med-name="{{ $med->name }}"
                                        data-med-category="{{ $med->category ?: 'Medicine' }}"
                                        data-med-unit="{{ $unit }}"
                                        data-med-stock="{{ $stock }}"
                                        {{ $stock <= 0 ? 'disabled' : '' }}>
                                    + {{ $med->name }} ({{ $stock }} {{ $unit }})
                                </button>
                            @endforeach
                        </div>
                        <div id="dispensedMedicinesInputs"></div>
                    </div>
                @endif
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

        {{-- Medical Record Photos --}}
        @php
            $existingImages = $record ? $record->images : collect();
            $remainingImageSlots = max(0, \App\Models\MedicalRecord::MAX_IMAGES - $existingImages->count());
        @endphp
        <div class="form-section-title">
            <i class="fas fa-images"></i> Medical Record Photos (Up to 5 images)
            <span class="ms-auto badge rounded-pill" style="background: #F5F1EA; color: #6B6358; font-size: 11px; font-weight: 700;">
                {{ $existingImages->count() }} / {{ \App\Models\MedicalRecord::MAX_IMAGES }} uploaded &middot; {{ $remainingImageSlots }} remaining
            </span>
        </div>

        @if($existingImages->isNotEmpty())
            <div class="row g-2 mb-3">
                @foreach($existingImages as $img)
                    @php $isStaffImg = $img->uploaded_by_role !== 'pet_owner'; @endphp
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-4 border bg-white h-100 d-flex flex-column gap-1" style="border-color: #EAE6DE !important;">
                            <img src="{{ $img->image_url }}" alt="Medical record photo"
                                 style="width: 100%; height: 110px; object-fit: cover; border-radius: 12px; background: #FAF6F0;">
                            <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap mt-1">
                                <span class="badge rounded-pill" style="background: {{ $isStaffImg ? '#C8E8D0' : '#F8E0A8' }}; color: {{ $isStaffImg ? '#2A6038' : '#705C30' }}; font-size: 10px;">
                                    {{ $isStaffImg ? 'Staff' : 'Pet Owner' }}
                                </span>
                                <small class="text-muted text-truncate" style="font-size: 10px;">{{ $img->uploaded_by_name ?: ($isStaffImg ? 'Staff' : 'Pet Owner') }}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div>
            <label class="f-label" for="recordImagesInput">
                {{ $remainingImageSlots > 0 ? "Attach Photos (up to {$remainingImageSlots} more)" : 'Maximum 5 photos reached' }}
            </label>
            <input type="file"
                   id="recordImagesInput"
                   name="images[]"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   multiple
                   class="f-input {{ ($fieldError('images') || $fieldError('images.*')) ? 'is-invalid' : '' }}"
                   data-max-remaining="{{ $remainingImageSlots }}"
                   {{ $remainingImageSlots === 0 ? 'disabled' : '' }}>
            <div class="f-help">Accepted formats: JPG, PNG, WebP, GIF (max 8 MB each; auto-resized cleanly for mobile & web).</div>
            @if($fieldError('images')) <div class="f-error">{{ $fieldError('images') }}</div> @endif
            @if($fieldError('images.*')) <div class="f-error">{{ $fieldError('images.*') }}</div> @endif
            <div id="recordImagesGuardMsg" class="f-error d-none"></div>
            <div id="recordImagesPreviewGrid" class="row g-2 mt-2"></div>
        </div>

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

        const prescriptionsEl = document.getElementById('prescriptions');
        const dispensedContainer = document.getElementById('dispensedMedicinesInputs');
        const quickPickBar = document.getElementById('medicineQuickPickBar');
        let dispensedIndex = 0;

        if (quickPickBar && prescriptionsEl && dispensedContainer) {
            quickPickBar.querySelectorAll('.med-chip-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    let remaining = parseInt(this.getAttribute('data-med-stock') || '0', 10);
                    if (remaining <= 0) return;

                    const medId = this.getAttribute('data-med-id');
                    const medName = this.getAttribute('data-med-name');
                    const medCategory = this.getAttribute('data-med-category') || 'Medicine';
                    const medUnit = this.getAttribute('data-med-unit') || 'units';

                    const line = `• ${medName} (${medCategory})`;
                    const current = prescriptionsEl.value.trim();
                    prescriptionsEl.value = current ? `${prescriptionsEl.value.replace(/\s+$/, '')}\n${line}` : line;

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = `dispensed_medicines[${dispensedIndex}][medicine_id]`;
                    idInput.value = medId;

                    const qtyInput = document.createElement('input');
                    qtyInput.type = 'hidden';
                    qtyInput.name = `dispensed_medicines[${dispensedIndex}][quantity]`;
                    qtyInput.value = '1';

                    dispensedContainer.appendChild(idInput);
                    dispensedContainer.appendChild(qtyInput);
                    dispensedIndex++;

                    remaining = Math.max(0, remaining - 1);
                    this.setAttribute('data-med-stock', String(remaining));
                    this.textContent = `+ ${medName} (${remaining} ${medUnit})`;
                    if (remaining <= 0) {
                        this.disabled = true;
                    }
                });
            });
        }

        const imagesInput = document.getElementById('recordImagesInput');
        const previewGrid = document.getElementById('recordImagesPreviewGrid');
        const guardMsg = document.getElementById('recordImagesGuardMsg');

        if (imagesInput && previewGrid) {
            imagesInput.addEventListener('change', function () {
                const maxRemaining = parseInt(this.getAttribute('data-max-remaining') || '5', 10);
                previewGrid.innerHTML = '';
                if (guardMsg) {
                    guardMsg.classList.add('d-none');
                    guardMsg.textContent = '';
                }

                const files = Array.from(this.files || []);
                if (files.length > maxRemaining) {
                    this.value = '';
                    if (guardMsg) {
                        guardMsg.textContent = `You can upload at most ${maxRemaining} more image(s) for this record (max 5 total).`;
                        guardMsg.classList.remove('d-none');
                    }
                    return;
                }

                files.forEach((file) => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        const col = document.createElement('div');
                        col.className = 'col-4 col-md-2';
                        col.innerHTML = `<div class="p-1 bg-white border rounded-3 text-center" style="border-color: #EAE6DE !important;">
                            <img src="${ev.target.result}" alt="Preview" style="width: 100%; height: 82px; object-fit: cover; border-radius: 10px;">
                        </div>`;
                        previewGrid.appendChild(col);
                    };
                    reader.readAsDataURL(file);
                });
            });
        }
    });
</script>
@endsection
