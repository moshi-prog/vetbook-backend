<?php $__env->startSection('title', 'Medical Record #' . $record->id); ?>

<?php $__env->startSection('styles'); ?>
    <?php echo $__env->make('admin.medical-records._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
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
?>

<a href="<?php echo e($pet ? route('admin.medical-records.pet', $pet->id) : route('admin.medical-records.index')); ?>" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Back to <?php echo e($pet ? $pet->name . "'s records" : 'Medical Records'); ?>

</a>

<div class="mr-card" style="background: #F5F1EA;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
                Medical Record #<?php echo e($record->id); ?>

            </h2>
            <p class="mr-subtitle">
                Consultation on <?php echo e($record->consultation_date->format('F j, Y')); ?>

                &middot; Recorded <?php echo e($record->created_at->format('M d, Y g:i A')); ?>

            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo e(route('admin.medical-records.edit', $record->id)); ?>" class="btn-soft">
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
            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="detail-block">
                    <div class="lbl"><?php echo e($label); ?></div>
                    <div class="val <?php echo e($value ? '' : 'empty'); ?>"><?php echo e($value ?: 'Not recorded'); ?></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mr-card">
            <h3><i class="fas fa-syringe me-2" style="color: #4A7C59;"></i>Vaccinations Given</h3>
            <?php $__empty_1 = true; $__currentLoopData = $vaxList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <span class="vax-pill ok"><i class="fas fa-circle-check"></i><?php echo e($vx); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="mr-subtitle mb-0">No vaccinations given at this visit.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mr-card">
            <h3><i class="fas fa-paw me-2" style="color: #4A7C59;"></i>Patient</h3>
            <?php if($pet): ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php echo $__env->make('admin.medical-records._avatar', ['pet' => $pet, 'size' => 52], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <div>
                        <a href="<?php echo e(route('admin.medical-records.pet', $pet->id)); ?>" class="tl-pet-name"><?php echo e($pet->name); ?></a>
                        <div class="tl-sub"><?php echo e($pet->species); ?><?php echo e($pet->breed ? ' · ' . $pet->breed : ''); ?></div>
                    </div>
                </div>
                <div class="info-row"><div class="k">Owner</div><div class="v"><?php echo e($pet->owner->name ?? 'Unknown'); ?></div></div>
                <div class="info-row"><div class="k">Phone</div><div class="v"><?php echo e($pet->owner->phone_number ?? '—'); ?></div></div>
                <div class="info-row"><div class="k">Age</div><div class="v"><?php echo e($pet->age !== null ? $pet->age . ' ' . \Illuminate\Support\Str::plural('yr', $pet->age) : '—'); ?></div></div>
                <div class="info-row"><div class="k">Gender</div><div class="v"><?php echo e($pet->gender ?? '—'); ?></div></div>
            <?php else: ?>
                <p class="mr-subtitle mb-0">Pet no longer exists.</p>
            <?php endif; ?>
        </div>

        <div class="mr-card">
            <h3><i class="fas fa-calendar-check me-2" style="color: #4A7C59;"></i>Visit</h3>
            <?php if(!empty($record->veterinarian) && $record->veterinarian !== 'Clinic Staff'): ?>
                <div class="info-row"><div class="k">Vet</div><div class="v"><?php echo e($record->veterinarian); ?></div></div>
            <?php endif; ?>
            <div class="info-row"><div class="k">Date</div><div class="v"><?php echo e($record->consultation_date->format('M d, Y')); ?></div></div>
            <div class="info-row">
                <div class="k">Next visit</div>
                <div class="v">
                    <?php if($record->next_visit_date): ?>
                        <span class="next-pill <?php echo e($record->next_visit_date->gte($today) ? 'upcoming' : 'past'); ?>">
                            <i class="fas fa-calendar-day"></i><?php echo e($record->next_visit_date->format('M d, Y')); ?>

                        </span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteMrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none;">
            <form method="POST" action="<?php echo e(route('admin.medical-records.destroy', $record->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Medical Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                    <p class="mb-2">Are you sure you want to delete Medical Record #<?php echo e($record->id); ?> for <strong><?php echo e($record->pet?->name ?? 'Pet'); ?></strong>?</p>
                    <p class="mb-0 text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> This cannot be undone.</p>
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/medical-records/show.blade.php ENDPATH**/ ?>