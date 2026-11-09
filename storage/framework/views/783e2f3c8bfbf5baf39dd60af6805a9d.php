<?php $__env->startSection('title', $record ? 'Edit Medical Record' : 'Add Medical Record'); ?>

<?php $__env->startSection('styles'); ?>
    <?php echo $__env->make('admin.medical-records._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
    $val = fn ($field, $default = '') => old($field, $record ? ($record->{$field} instanceof \Carbon\Carbon
        ? $record->{$field}->format('Y-m-d') : $record->{$field}) : $default);
    $fieldError = fn ($f) => $errors->first($f);
?>

<a href="<?php echo e($record ? route('admin.medical-records.show', $record->id) : route('admin.medical-records.index')); ?>" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Cancel and go back
</a>

<div class="mb-4">
    <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
        <?php echo e($record ? 'Edit Medical Record' : 'Add Medical Record'); ?>

    </h2>
    <p class="mr-subtitle">Fields marked <span style="color: #B83230;">*</span> are required.</p>
</div>

<form method="POST"
      action="<?php echo e($record ? route('admin.medical-records.update', $record->id) : route('admin.medical-records.store')); ?>">
    <?php echo csrf_field(); ?>
    <?php if($record): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

    <div class="mr-card">
        
        <div class="form-section-title"><i class="fas fa-calendar-check"></i> Visit Details</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="f-label" for="pet_id">Pet <span class="req">*</span></label>
                <select id="pet_id" name="pet_id" class="f-input <?php echo e($fieldError('pet_id') ? 'is-invalid' : ''); ?>"
                        placeholder="Search by pet or owner name..." required>
                    <option value="">Select a pet...</option>
                    <?php $__currentLoopData = $pets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>" <?php if((string) old('pet_id', $selectedPetId) === (string) $p->id): echo 'selected'; endif; ?>>
                            <?php echo e($p->name); ?> (<?php echo e($p->species ?: 'Pet'); ?>) — <?php echo e($p->owner->name ?? 'Unknown owner'); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php if($fieldError('pet_id')): ?> <div class="f-error"><?php echo e($fieldError('pet_id')); ?></div> <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="f-label" for="consultation_date">Consultation date <span class="req">*</span></label>
                <input type="date" id="consultation_date" name="consultation_date"
                       class="f-input <?php echo e($fieldError('consultation_date') ? 'is-invalid' : ''); ?>"
                       value="<?php echo e($val('consultation_date', now()->format('Y-m-d'))); ?>" required>
                <?php if($fieldError('consultation_date')): ?> <div class="f-error"><?php echo e($fieldError('consultation_date')); ?></div> <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="f-label" for="next_visit_date">Next visit date</label>
                <input type="date" id="next_visit_date" name="next_visit_date"
                       class="f-input <?php echo e($fieldError('next_visit_date') ? 'is-invalid' : ''); ?>"
                       value="<?php echo e($val('next_visit_date')); ?>">
                <div class="f-help">Optional. Used for follow-ups and vaccine reminders.</div>
                <?php if($fieldError('next_visit_date')): ?> <div class="f-error"><?php echo e($fieldError('next_visit_date')); ?></div> <?php endif; ?>
            </div>
        </div>

        
        <div class="form-section-title"><i class="fas fa-stethoscope"></i> Diagnosis &amp; Treatment</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="f-label" for="diagnosis">Diagnosis</label>
                <textarea id="diagnosis" name="diagnosis" rows="4"
                          class="f-input <?php echo e($fieldError('diagnosis') ? 'is-invalid' : ''); ?>"><?php echo e($val('diagnosis')); ?></textarea>
                <?php if($fieldError('diagnosis')): ?> <div class="f-error"><?php echo e($fieldError('diagnosis')); ?></div> <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="f-label" for="treatment">Treatment</label>
                <textarea id="treatment" name="treatment" rows="4"
                          class="f-input <?php echo e($fieldError('treatment') ? 'is-invalid' : ''); ?>"><?php echo e($val('treatment')); ?></textarea>
                <?php if($fieldError('treatment')): ?> <div class="f-error"><?php echo e($fieldError('treatment')); ?></div> <?php endif; ?>
            </div>
        </div>

        
        <div class="form-section-title"><i class="fas fa-syringe"></i> Vaccinations &amp; Prescriptions</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="f-label" for="vaccinations">Vaccinations</label>
                <textarea id="vaccinations" name="vaccinations" rows="4"
                          class="f-input <?php echo e($fieldError('vaccinations') ? 'is-invalid' : ''); ?>"
                          placeholder="e.g. Rabies, 5-in-1, Bordetella"><?php echo e($val('vaccinations')); ?></textarea>
                <div class="f-help">Comma-separated. Each vaccine appears on the pet's vaccination summary.</div>
                <?php if($fieldError('vaccinations')): ?> <div class="f-error"><?php echo e($fieldError('vaccinations')); ?></div> <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="f-label" for="prescriptions">Prescriptions</label>
                <textarea id="prescriptions" name="prescriptions" rows="4"
                          class="f-input <?php echo e($fieldError('prescriptions') ? 'is-invalid' : ''); ?>"><?php echo e($val('prescriptions')); ?></textarea>
                <?php if($fieldError('prescriptions')): ?> <div class="f-error"><?php echo e($fieldError('prescriptions')); ?></div> <?php endif; ?>
            </div>
        </div>

        
        <div class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</div>
        <textarea id="notes" name="notes" rows="4"
                  class="f-input <?php echo e($fieldError('notes') ? 'is-invalid' : ''); ?>"><?php echo e($val('notes')); ?></textarea>
        <?php if($fieldError('notes')): ?> <div class="f-error"><?php echo e($fieldError('notes')); ?></div> <?php endif; ?>

        <div class="form-actions">
            <a href="<?php echo e($record ? route('admin.medical-records.show', $record->id) : route('admin.medical-records.index')); ?>"
               class="btn-soft">Cancel</a>
            <button type="submit" class="btn-green">
                <i class="fas fa-floppy-disk"></i> <?php echo e($record ? 'Save Changes' : 'Save Record'); ?>

            </button>
        </div>
    </div>
</form>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new TomSelect('#pet_id', { create: false, maxOptions: null, allowEmptyOption: true });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/medical-records/form.blade.php ENDPATH**/ ?>