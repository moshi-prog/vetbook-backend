<?php $__env->startSection('title', $pet->name . ' — Records'); ?>

<?php $__env->startSection('styles'); ?>
    <?php echo $__env->make('admin.medical-records._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
    $statusLabels = ['ok' => 'Up to date', 'soon' => 'Due soon', 'overdue' => 'Overdue'];
    $seenAt = $pet->appointments->pluck('branch')->filter()->unique()->values();
?>

<a href="<?php echo e(route('admin.medical-records.index')); ?>" class="back-link">
    <i class="fas fa-arrow-left me-1"></i> Back to Medical Records
</a>


<div class="mr-card" style="background: #F5F1EA;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <?php echo $__env->make('admin.medical-records._avatar', ['pet' => $pet, 'size' => 64], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div>
                <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin: 0;"><?php echo e($pet->name); ?></h2>
                <div class="mr-subtitle">
                    <?php echo e($pet->species ?: 'Pet'); ?><?php echo e($pet->breed ? ' · ' . $pet->breed : ''); ?>

                    <?php if($pet->age !== null): ?> &middot; <?php echo e($pet->age); ?> <?php echo e(\Illuminate\Support\Str::plural('yr', $pet->age)); ?> <?php endif; ?>
                    <?php if($pet->gender): ?> &middot; <?php echo e($pet->gender); ?> <?php endif; ?>
                    <?php if($pet->weight): ?> &middot; <?php echo e(rtrim(rtrim($pet->weight, '0'), '.')); ?> kg <?php endif; ?>
                </div>
                <div class="mr-subtitle mt-1">
                    <i class="fas fa-user me-1" style="color: #4A7C59;"></i>
                    <?php echo e($pet->owner->name ?? 'Unknown owner'); ?>

                    <?php if($pet->owner?->phone_number): ?> &middot; <?php echo e($pet->owner->phone_number); ?> <?php endif; ?>
                </div>
                <div class="mt-2">
                    <?php $__currentLoopData = $seenAt; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="branch-chip <?php echo e($branch && $b === $branch ? 'mine' : ''); ?>">
                            <i class="fas fa-location-dot"></i><?php echo e(\Illuminate\Support\Str::before($b, ' Branch')); ?>

                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
        <a href="<?php echo e(route('admin.medical-records.create', ['pet_id' => $pet->id])); ?>" class="btn-green">
            <i class="fas fa-plus"></i> Add Medical Record
        </a>
    </div>
    <?php if($pet->medical_conditions): ?>
        <div class="mt-3 p-3" style="background: #FFFFFF; border-radius: 16px; font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #2E3230;">
            <strong style="color: #705C30;"><i class="fas fa-triangle-exclamation me-1"></i>Known conditions:</strong>
            <?php echo e($pet->medical_conditions); ?>

        </div>
    <?php endif; ?>
</div>

<?php if($records->isEmpty()): ?>
    <div class="mr-card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-file-medical"></i></div>
            <h4>No records for this pet yet</h4>
            <p>Add the first medical record after <?php echo e($pet->name); ?>'s consultation.</p>
            <a href="<?php echo e(route('admin.medical-records.create', ['pet_id' => $pet->id])); ?>" class="btn-green">
                <i class="fas fa-plus"></i> Add Record
            </a>
        </div>
    </div>
<?php else: ?>
    
    <div class="mr-card">
        <h3><i class="fas fa-syringe me-2" style="color: #4A7C59;"></i>Vaccinations</h3>
        <?php if(count($vaccinations)): ?>
            <?php $__currentLoopData = $vaccinations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span class="vax-pill <?php echo e($v['status']); ?>"
                      title="<?php echo e($statusLabels[$v['status']]); ?><?php echo e($v['next_visit'] ? ' · follow-up ' . $v['next_visit']->format('M d, Y') : ''); ?>">
                    <i class="fas <?php echo e($v['status'] === 'overdue' ? 'fa-circle-exclamation' : ($v['status'] === 'soon' ? 'fa-clock' : 'fa-circle-check')); ?>"></i>
                    <?php echo e($v['name']); ?>

                    <small><?php echo e($v['last_given']->format('M d, Y')); ?></small>
                </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div class="mr-subtitle mt-2" style="font-size: 12px;">
                <span class="vax-pill ok" style="padding: 2px 10px; font-size: 11px;">Up to date</span>
                <span class="vax-pill soon" style="padding: 2px 10px; font-size: 11px;">Follow-up within 30 days</span>
                <span class="vax-pill overdue" style="padding: 2px 10px; font-size: 11px;">Follow-up overdue</span>
                Dates show when each vaccine was last given.
            </div>
        <?php else: ?>
            <p class="mr-subtitle mb-0">No vaccinations recorded yet.</p>
        <?php endif; ?>
    </div>

    
    <div class="tl-month" style="margin-top: 0;">
        <?php echo e($records->count()); ?> <?php echo e(\Illuminate\Support\Str::plural('record', $records->count())); ?>

    </div>
    <?php echo $__env->make('admin.medical-records._timeline', ['records' => $records, 'showPet' => false, 'branch' => $branch], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/medical-records/pet.blade.php ENDPATH**/ ?>