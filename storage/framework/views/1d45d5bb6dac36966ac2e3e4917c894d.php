<?php $__env->startSection('title', 'Medical Records'); ?>

<?php $__env->startSection('styles'); ?>
    <?php echo $__env->make('admin.medical-records._styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
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
?>


<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h2 style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230; margin-bottom: 4px;">
            Medical Records
        </h2>
        <p class="mr-subtitle">
            <strong style="color: #2E3230;"><?php echo e(number_format($stats['total'])); ?></strong>
            <?php echo e(\Illuminate\Support\Str::plural('record', $stats['total'])); ?> on file
        </p>
        <div class="branch-note">
            <i class="fas fa-location-dot"></i>
            <?php if($branch): ?>
                Showing all records &middot; branch tags highlight visits to <?php echo e($branch); ?>

            <?php else: ?>
                Showing all records across all branches
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo e(route('admin.medical-records.export', request()->query())); ?>" target="_blank"
           class="btn" style="background: #FAF6F0; color: #4A7C59; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; padding: 10px 18px; border: 1px solid #EAE6DE; border-radius: 9999px; text-decoration: none;"
           onmouseover="this.style.background='#F0ECE4'" onmouseout="this.style.background='#FAF6F0'">
            <i class="fas fa-print me-1"></i> Export / Print Report
        </a>
        <a href="<?php echo e(route('admin.medical-records.create')); ?>" class="btn-green">
            <i class="fas fa-plus"></i> Add Medical Record
        </a>
    </div>
</div>


<div class="mr-stats">
    <?php $__currentLoopData = $statCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon, $bg, $fg]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="mr-stat">
            <div class="mr-stat-icon" style="background: <?php echo e($bg); ?>; color: <?php echo e($fg); ?>;"><i class="fas <?php echo e($icon); ?>"></i></div>
            <div>
                <div class="mr-stat-value"><?php echo e(number_format($value)); ?></div>
                <div class="mr-stat-label"><?php echo e($label); ?></div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="filter-panel">
    <div class="filter-tabs">
        <?php $__currentLoopData = $periods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($periodUrl($key)); ?>" class="filter-tab <?php echo e($filters['period'] === $key ? 'active' : ''); ?>">
                <?php echo e($label); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <form method="GET" action="<?php echo e(route('admin.medical-records.index')); ?>" class="search-row">
        <?php if($filters['period'] !== 'all'): ?>
            <input type="hidden" name="period" value="<?php echo e($filters['period']); ?>">
        <?php endif; ?>
        <div class="input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="<?php echo e($filters['search']); ?>"
                   placeholder="Search by pet, owner, or diagnosis...">
        </div>
        <button type="submit" class="filter-tab active" style="padding: 10px 24px;">Search</button>
        <?php if($hasFilters): ?>
            <a href="<?php echo e(route('admin.medical-records.index')); ?>" class="clear-link">
                <i class="fas fa-xmark me-1"></i> Clear filters
            </a>
        <?php endif; ?>
    </form>
</div>


<?php if($records->isEmpty()): ?>
    <div class="mr-card">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-file-medical"></i></div>
            <h4><?php echo e($hasFilters ? 'No matching records' : 'No medical records yet'); ?></h4>
            <?php if($hasFilters): ?>
                <p>Try adjusting your search or filters.</p>
                <a href="<?php echo e(route('admin.medical-records.index')); ?>" class="filter-tab active">Clear filters</a>
            <?php else: ?>
                <p>Records added after consultations will appear here as a timeline.</p>
                <a href="<?php echo e(route('admin.medical-records.create')); ?>" class="btn-green">
                    <i class="fas fa-plus"></i> Add the first record
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex align-items-center justify-content-between p-3 mb-3 bg-white rounded-4 border" style="box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
        <label class="d-flex align-items-center gap-2 mb-0" style="cursor: pointer; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; color: #4A4E4A;">
            <input type="checkbox" id="selectAll" style="cursor: pointer; width: 18px; height: 18px; accent-color: #4A7C59;">
            Select All Records on Page
        </label>
        <span class="text-muted small">Check multiple records to perform bulk actions</span>
    </div>

    <?php echo $__env->make('admin.medical-records._timeline', ['records' => $records, 'showPet' => true, 'branch' => $branch], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="pagination-wrap">
        <div class="pagination-info">Showing <?php echo e($records->firstItem()); ?>–<?php echo e($records->lastItem()); ?> of <?php echo e($records->total()); ?></div>
        <?php echo e($records->links()); ?>

    </div>

    <?php echo $__env->make('admin.common._batch_actions', [
        'module' => 'medical-records',
        'itemName' => 'medical record(s)',
        'bulkDeleteRoute' => route('admin.medical-records.bulk-delete'),
        'exportRoute' => route('admin.medical-records.export'),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/medical-records/index.blade.php ENDPATH**/ ?>