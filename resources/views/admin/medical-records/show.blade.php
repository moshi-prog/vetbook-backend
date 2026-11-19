@extends('admin.layouts.app')

@section('title', 'Medical Record #' . $record->id)

@section('styles')
    @include('admin.medical-records._styles')
<style>
    .detail-block { margin-bottom: 20px; }
    .detail-block:last-child { margin-bottom: 0; }
    .detail-block .lbl {
        font-family: 'Nunito Sans', sans-serif; font-size: 11px; font-weight: 700; color: #6B6358;
        text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;
    }
    .detail-block .val {
        font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #2E3230;
        white-space: pre-wrap; background: #FAF6F0; border-radius: 16px; padding: 14px 16px;
    }
    .detail-block .val.empty { color: #6B6358; font-style: italic; }
    .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #EAE6DE; font-family: 'Nunito Sans', sans-serif; }
    .info-row:last-child { border-bottom: none; }
    .info-row .k { width: 120px; font-size: 12px; font-weight: 600; color: #6B6358; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-row .v { flex: 1; font-size: 14px; color: #2E3230; }
</style>
@endsection

@section('content')

@php
    $pet = $record->pet;
    $vaxList = $record->vaccinations
        ? array_values(array_filter(array_map('trim', preg_split('/[,;\n\r]+/', $record->vaccinations))))
        : [];
    $today = \Carbon\Carbon::today();
    $fields = [
        'Diagnosis' => $record->diagnosis,
        'Treatment' => $record->treatment,
        'Prescriptions' => $record->prescriptions,
        'Notes' => $record->notes,
    ];
@endphp

<a href="{{ $pet ? route('admin.medical-records.pet', $pet->id) : route('admin.medical-records.index') }}" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Back to {{ $pet ? $pet->name . "'s records" : 'Medical Records' }}
</a>

<div class="mr-card" style="background: #F5F1EA;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
                Medical Record #{{ $record->id }}
            </h2>
            <p class="mr-subtitle">
                Consultation on {{ $record->consultation_date?->format('F j, Y') ?? '—' }}
                &middot; Recorded {{ $record->created_at?->format('M d, Y g:i A') ?? '—' }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.medical-records.edit', $record->id) }}" class="btn-soft">
                <i class="fas fa-pen"></i> Edit
            </a>
            <button type="button" class="btn-danger-soft" data-bs-toggle="modal" data-bs-target="#deleteMrModal">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="mr-card">
            <h3><i class="fas fa-stethoscope me-2" style="color: #4A7C59;"></i>Consultation</h3>
            @foreach($fields as $label => $value)
                <div class="detail-block">
                    <div class="lbl">{{ $label }}</div>
                    <div class="val {{ $value ? '' : 'empty' }}">{{ $value ?: 'Not recorded' }}</div>
                </div>
            @endforeach
        </div>

        <div class="mr-card">
            <h3><i class="fas fa-syringe me-2" style="color: #4A7C59;"></i>Vaccinations Given</h3>
            @forelse($vaxList as $vx)
                <span class="vax-pill ok"><i class="fas fa-circle-check"></i>{{ $vx }}</span>
            @empty
                <p class="mr-subtitle mb-0">No vaccinations given at this visit.</p>
            @endforelse
        </div>

        {{-- Shared Medical Record Photos (up to 5) --}}
        @php
            $recordImages = $record->images ?? collect();
            $remainingSlots = max(0, \App\Models\MedicalRecord::MAX_IMAGES - $recordImages->count());
        @endphp
        <div class="mr-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h3 class="mb-0">
                    <i class="fas fa-images me-2" style="color: #4A7C59;"></i>Medical Record Photos ({{ $recordImages->count() }} / 5)
                </h3>
                <span class="badge rounded-pill px-3 py-2" style="background: #F5F1EA; color: #6B6358; font-size: 11px;">
                    Shared with Pet Owner
                </span>
            </div>

            @if($recordImages->isNotEmpty())
                <div class="row g-3 mb-3">
                    @foreach($recordImages as $img)
                        @php $isStaffImg = $img->uploaded_by_role !== 'pet_owner'; @endphp
                        <div class="col-6 col-md-4">
                            <div class="p-2 rounded-4 border bg-white h-100 d-flex flex-column justify-content-between" style="border-color: #EAE6DE !important;">
                                <div>
                                    <a href="javascript:void(0)"
                                       class="d-block record-lightbox-trigger"
                                       data-img-src="{{ $img->image_url }}"
                                       data-img-role="{{ $isStaffImg ? 'Staff' : 'Pet Owner' }}"
                                       data-img-uploader="{{ $img->uploaded_by_name ?: ($isStaffImg ? 'Clinic Staff' : 'Pet Owner') }}"
                                       data-img-date="{{ $img->created_at?->format('M d, Y g:i A') ?? '' }}">
                                        <img src="{{ $img->image_url }}"
                                             alt="Medical Record Photo #{{ $loop->iteration }}"
                                             style="width: 100%; height: 140px; object-fit: cover; border-radius: 14px; background: #FAF6F0; cursor: zoom-in;">
                                    </a>
                                    <div class="d-flex align-items-center justify-content-between gap-1 mt-2 flex-wrap">
                                        <span class="badge rounded-pill" style="background: {{ $isStaffImg ? '#C8E8D0' : '#F8E0A8' }}; color: {{ $isStaffImg ? '#2A6038' : '#705C30' }}; font-size: 10px;">
                                            {{ $isStaffImg ? 'Staff' : 'Pet Owner' }}
                                        </span>
                                        <small class="text-muted" style="font-size: 11px;">
                                            {{ $img->created_at?->format('M d, Y') ?? '' }}
                                        </small>
                                    </div>
                                    <div class="text-truncate mt-1" style="font-size: 12px; font-weight: 600; color: #2E3230;">
                                        {{ $img->uploaded_by_name ?: ($isStaffImg ? 'Clinic Staff' : 'Pet Owner') }}
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 d-flex justify-content-end" style="border-top: 1px solid #F0ECE4;">
                                    <form method="POST" action="{{ route('admin.medical-records.images.destroy', [$record->id, $img->id]) }}"
                                          onsubmit="return confirm('Remove this photo from the medical record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" style="font-size: 11px;">
                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mr-subtitle mb-3">No photos attached yet. Staff or the Pet Owner can upload up to 5 photos.</p>
            @endif

            @if($remainingSlots > 0)
                <form method="POST"
                      action="{{ route('admin.medical-records.images.store', $record->id) }}"
                      enctype="multipart/form-data"
                      class="p-3 rounded-4"
                      style="background: #FAF6F0; border: 1px dashed #C4C8BC;">
                    @csrf
                    <label for="showRecordImagesInput" class="form-label fw-bold small mb-2" style="color: #2E3230;">
                        <i class="fas fa-cloud-upload-alt me-1 text-success"></i>
                        Upload Additional Photos (up to {{ $remainingSlots }} remaining)
                    </label>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="file"
                               id="showRecordImagesInput"
                               name="images[]"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               multiple
                               required
                               class="form-control"
                               data-max-remaining="{{ $remainingSlots }}"
                               style="border-radius: 14px; flex: 1; min-width: 220px;">
                        <button type="submit" class="btn-green" style="white-space: nowrap;">
                            <i class="fas fa-upload"></i> Upload Photos
                        </button>
                    </div>
                    <div id="showRecordImagesGuardMsg" class="text-danger small fw-bold mt-2 d-none"></div>
                    @if(isset($errors) && $errors->has('images'))
                        <div class="text-danger small fw-bold mt-2">{{ $errors->first('images') }}</div>
                    @endif
                    @if(isset($errors) && $errors->has('images.*'))
                        <div class="text-danger small fw-bold mt-2">{{ $errors->first('images.*') }}</div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mr-card">
            <h3><i class="fas fa-paw me-2" style="color: #4A7C59;"></i>Patient</h3>
            @if($pet)
                <div class="d-flex align-items-center gap-3 mb-3">
                    @include('admin.medical-records._avatar', ['pet' => $pet, 'size' => 52])
                    <div>
                        <a href="{{ route('admin.medical-records.pet', $pet->id) }}" class="tl-pet-name">{{ $pet->name }}</a>
                        <div class="tl-sub">{{ $pet->species }}{{ $pet->breed ? ' · ' . $pet->breed : '' }}</div>
                    </div>
                </div>
                <div class="info-row"><div class="k">Owner</div><div class="v">{{ $pet->owner->name ?? 'Unknown' }}</div></div>
                <div class="info-row"><div class="k">Phone</div><div class="v">{{ $pet->owner->phone_number ?? '—' }}</div></div>
                <div class="info-row"><div class="k">Age</div><div class="v">{{ $pet->age !== null ? $pet->age . ' ' . \Illuminate\Support\Str::plural('yr', $pet->age) : '—' }}</div></div>
                <div class="info-row"><div class="k">Gender</div><div class="v">{{ $pet->gender ?? '—' }}</div></div>
            @else
                <p class="mr-subtitle mb-0">Pet no longer exists.</p>
            @endif
        </div>

        <div class="mr-card">
            <h3><i class="fas fa-calendar-check me-2" style="color: #4A7C59;"></i>Visit</h3>
            @if(!empty($record->veterinarian) && $record->veterinarian !== 'Clinic Staff')
                <div class="info-row"><div class="k">Vet</div><div class="v">{{ $record->veterinarian }}</div></div>
            @endif
            <div class="info-row"><div class="k">Date</div><div class="v">{{ $record->consultation_date?->format('M d, Y') ?? '—' }}</div></div>
            <div class="info-row">
                <div class="k">Next visit</div>
                <div class="v">
                    @if($record->next_visit_date)
                        <span class="next-pill {{ $record->next_visit_date->gte($today) ? 'upcoming' : 'past' }}">
                            <i class="fas fa-calendar-day"></i>{{ $record->next_visit_date->format('M d, Y') }}
                        </span>
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Lightbox Modal for Medical Record Photos --}}
<div class="modal fade" id="recordImageLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; background: #FAF6F0;">
            <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                <div>
                    <span id="lightboxRoleBadge" class="badge rounded-pill me-2" style="font-size: 11px;"></span>
                    <strong id="lightboxUploaderName" style="font-family: 'Literata', serif; color: #2E3230;"></strong>
                    <div id="lightboxUploadedDate" class="small text-muted"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="lightboxPreviewImg" src="" alt="Medical Record Full Image"
                     style="max-width: 100%; max-height: 75vh; border-radius: 16px; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

{{-- Delete Medical Record Modal --}}
<div class="modal fade" id="deleteMrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <form method="POST" action="{{ route('admin.medical-records.destroy', $record->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Medical Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                    <p class="mb-2">Are you sure you want to delete Medical Record #{{ $record->id }} for <strong>{{ $record->pet?->name ?? 'Pet' }}</strong>?</p>
                    <div class="p-2 rounded mt-2" style="background: #E8F5E9; border: 1px solid rgba(46,125,50,0.25); color: #1B5E20; font-size: 12px;">
                        <i class="fas fa-shield-alt me-1"></i> <strong>Safe Deletion:</strong> The pet profile and appointments will remain completely untouched.
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

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lightboxEl = document.getElementById('recordImageLightboxModal');
        const lightboxImg = document.getElementById('lightboxPreviewImg');
        const roleBadge = document.getElementById('lightboxRoleBadge');
        const uploaderName = document.getElementById('lightboxUploaderName');
        const uploadedDate = document.getElementById('lightboxUploadedDate');

        if (lightboxEl && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(lightboxEl);
            document.querySelectorAll('.record-lightbox-trigger').forEach(trigger => {
                trigger.addEventListener('click', function () {
                    const role = this.getAttribute('data-img-role') || 'Staff';
                    lightboxImg.src = this.getAttribute('data-img-src') || '';
                    roleBadge.textContent = role;
                    roleBadge.style.background = role === 'Pet Owner' ? '#F8E0A8' : '#C8E8D0';
                    roleBadge.style.color = role === 'Pet Owner' ? '#705C30' : '#2A6038';
                    uploaderName.textContent = this.getAttribute('data-img-uploader') || '';
                    uploadedDate.textContent = this.getAttribute('data-img-date') || '';
                    modal.show();
                });
            });
        }

        const uploadInput = document.getElementById('showRecordImagesInput');
        const guardMsg = document.getElementById('showRecordImagesGuardMsg');
        if (uploadInput) {
            uploadInput.addEventListener('change', function () {
                const maxRemaining = parseInt(this.getAttribute('data-max-remaining') || '5', 10);
                if (guardMsg) {
                    guardMsg.classList.add('d-none');
                    guardMsg.textContent = '';
                }
                if ((this.files || []).length > maxRemaining) {
                    this.value = '';
                    if (guardMsg) {
                        guardMsg.textContent = `Maximum of 5 images allowed per record. Select at most ${maxRemaining} photo(s).`;
                        guardMsg.classList.remove('d-none');
                    }
                }
            });
        }
    });
</script>
@endsection
