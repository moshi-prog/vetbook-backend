<?php $__env->startSection('title', $pet->name); ?>

<?php
    $speciesStyles = [
        'dog'    => ['#C8E8D0', '#2A6038', 'fa-dog',    '#C8E8D0', '#2A6038'],
        'cat'    => ['#F8E0A8', '#705C30', 'fa-cat',    '#F8E0A8', '#705C30'],
        'bird'   => ['#D8E6F0', '#35607E', 'fa-dove',   '#D8E6F0', '#35607E'],
        'rabbit' => ['#FFDAD8', '#B83230', 'fa-carrot', '#FFDAD8', '#B83230'],
        'other'  => ['#EAE6DE', '#4A4E4A', 'fa-paw',    '#EAE6DE', '#4A4E4A'],
    ];
    $speciesKey = strtolower((string) ($pet->species ?? 'other'));
    $speciesConfig = $speciesStyles[$speciesKey] ?? $speciesStyles['other'];

    $avatarSrc = ($pet->avatar_url ?? null)
        ? (\Illuminate\Support\Str::startsWith($pet->avatar_url, ['http://', 'https://', '//'])
            ? $pet->avatar_url
            : asset('storage/' . ltrim($pet->avatar_url, '/')))
        : null;

    $gender = strtolower((string) ($pet->gender ?? ''));
    $genderIcon = match($gender) {
        'male' => 'fa-mars',
        'female' => 'fa-venus',
        default => 'fa-genderless',
    };
    $genderColor = match($gender) {
        'male' => '#2A6038',
        'female' => '#B83230',
        default => '#4A4E4A',
    };
    $genderBg = match($gender) {
        'male' => '#C8E8D0',
        'female' => '#FFDAD8',
        default => '#EAE6DE',
    };
?>

<?php $__env->startSection('styles'); ?>
<style>
    .back-link {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        color: #4A7C59;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color 0.15s;
    }
    .back-link:hover {
        color: #2A6038;
    }

    .pet-banner {
        background: #F5F1EA;
        border-radius: 24px;
        padding: 28px;
        margin-bottom: 24px;
        display: flex;
        gap: 24px;
        align-items: center;
        flex-wrap: wrap;
    }
    .pet-banner h1 {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        margin: 0 0 6px;
    }
    .pet-avatar-hero {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #FFFFFF;
        box-shadow: 0 4px 16px rgba(46,50,48,0.1);
        flex-shrink: 0;
    }
    .pet-avatar-fallback {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        border: 3px solid #FFFFFF;
        box-shadow: 0 4px 16px rgba(46,50,48,0.1);
        flex-shrink: 0;
    }

    .stat-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .stat-value {
        font-family: 'Literata', serif;
        font-size: 22px;
        font-weight: 700;
        color: #2E3230;
        line-height: 1.1;
    }
    .stat-label {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-top: 6px;
    }
    @media (max-width: 991px) { .stat-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px)  { .stat-row { grid-template-columns: 1fr; } }

    .section-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        margin-bottom: 24px;
    }
    .section-card-title {
        font-family: 'Literata', serif;
        font-size: 18px;
        font-weight: 700;
        color: #2E3230;
        margin: 0;
    }

    .info-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid #EAE6DE;
        font-family: 'Nunito Sans', sans-serif;
        align-items: center;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-row .k {
        width: 110px;
        font-size: 12px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-row .v {
        flex: 1;
        font-size: 14px;
        color: #2E3230;
    }

    .custom-table {
        width: 100%;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        margin: 0;
    }
    .custom-table th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B6358;
        padding: 12px 14px;
        background: #F5F1EA;
        border-bottom: 1px solid #EAE6DE;
        white-space: nowrap;
    }
    .custom-table td {
        padding: 14px;
        border-bottom: 1px solid #EAE6DE;
        color: #2E3230;
        vertical-align: middle;
    }
    .custom-table tbody tr {
        transition: background 0.15s;
    }
    .custom-table tbody tr:hover {
        background: rgba(245,241,234,0.6);
    }
    .custom-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
    }
    .status-pill.pending { background: #F8E0A8; color: #554020; }
    .status-pill.approved { background: #C8E8D0; color: #2A6038; }
    .status-pill.completed { background: #EAE6DE; color: #4A4E4A; }
    .status-pill.cancelled { background: #FFDAD8; color: #690005; }

    .branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 9999px;
        background: #F0ECE4;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }

    .empty-note {
        text-align: center;
        padding: 32px 16px;
        color: #6B6358;
        font-family: 'Nunito Sans', sans-serif;
    }
    .empty-note i {
        font-size: 32px;
        opacity: 0.35;
        display: block;
        margin-bottom: 10px;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a href="<?php echo e(route('admin.pets.index')); ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Pets
    </a>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo e(route('admin.medical-records.create', ['pet_id' => $pet->id])); ?>"
           style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 9999px; background: #FAF6F0; color: #2E3230; border: 1px solid #EAE6DE; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.15s;"
           onmouseover="this.style.background='#F0ECE4'" onmouseout="this.style.background='#FAF6F0'">
            <i class="fas fa-plus text-success"></i> Add Medical Record
        </a>
        <a href="<?php echo e(route('admin.medical-records.pet', $pet->id)); ?>"
           style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 9999px; background: #4A7C59; color: #FFFFFF; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.15s;"
           onmouseover="this.style.background='#3d6b4a'" onmouseout="this.style.background='#4A7C59'">
            <i class="fas fa-file-medical"></i> View Medical Records
        </a>
        <button type="button"
                data-bs-toggle="modal" data-bs-target="#deletePetModal"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 9999px; background: #FFDAD8; color: #B83230; border: 1px solid rgba(184,50,48,0.3); font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s;"
                onmouseover="this.style.background='#ffc4c2'" onmouseout="this.style.background='#FFDAD8'">
            <i class="fas fa-trash-alt"></i> Delete Pet
        </button>
    </div>
</div>


<div class="pet-banner">
    <?php if($avatarSrc): ?>
        <img src="<?php echo e($avatarSrc); ?>" alt="<?php echo e($pet->name); ?>" class="pet-avatar-hero"
             onerror="this.style.display='none'; document.getElementById('pet-avatar-fb').style.display='inline-flex';">
        <div id="pet-avatar-fb" class="pet-avatar-fallback" style="display: none; background: <?php echo e($speciesConfig[3]); ?>; color: <?php echo e($speciesConfig[4]); ?>;">
            <i class="fas <?php echo e($speciesConfig[2]); ?>"></i>
        </div>
    <?php else: ?>
        <div class="pet-avatar-fallback" style="background: <?php echo e($speciesConfig[3]); ?>; color: <?php echo e($speciesConfig[4]); ?>;">
            <i class="fas <?php echo e($speciesConfig[2]); ?>"></i>
        </div>
    <?php endif; ?>

    <div style="flex: 1; min-width: 240px;">
        <h1><?php echo e($pet->name); ?></h1>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge-chip" style="background: <?php echo e($speciesConfig[0]); ?>; color: <?php echo e($speciesConfig[1]); ?>;">
                <i class="fas <?php echo e($speciesConfig[2]); ?>"></i> <?php echo e(ucfirst($pet->species ?: 'Pet')); ?>

            </span>
            <span class="badge-chip" style="background: <?php echo e($genderBg); ?>; color: <?php echo e($genderColor); ?>;">
                <i class="fas <?php echo e($genderIcon); ?>"></i> <?php echo e(ucfirst($pet->gender ?: 'Unknown Gender')); ?>

            </span>
            <?php if($pet->breed): ?>
                <span class="badge-chip" style="background: #EAE6DE; color: #4A4E4A;">
                    <i class="fas fa-paw"></i> <?php echo e($pet->breed); ?>

                </span>
            <?php endif; ?>
        </div>
        <div style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #6B6358;">
            Owner:
            <?php if($pet->owner): ?>
                <a href="<?php echo e(route('admin.pet-owners.show', $pet->owner->id)); ?>" style="color: #4A7C59; font-weight: 700; text-decoration: none;">
                    <i class="fas fa-user ms-1 me-1"></i><?php echo e($pet->owner->name); ?>

                </a>
            <?php else: ?>
                <span class="fst-italic">No owner linked</span>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="stat-row">
    
    <div class="stat">
        <div class="stat-icon" style="background: #C8E8D0; color: #2A6038;">
            <i class="fas fa-weight-scale"></i>
        </div>
        <div>
            <div class="stat-value">
                <?php echo e($pet->weight !== null ? number_format($pet->weight, 1) . ' kg' : 'Not recorded'); ?>

            </div>
            <div class="stat-label">Weight</div>
        </div>
    </div>

    
    <div class="stat">
        <div class="stat-icon" style="background: #F8E0A8; color: #705C30;">
            <i class="fas fa-cake-candles"></i>
        </div>
        <div>
            <div class="stat-value">
                <?php echo e($pet->age !== null ? $pet->age . ' ' . \Illuminate\Support\Str::plural('yr', $pet->age) . ' old' : '—'); ?>

            </div>
            <div class="stat-label">Age</div>
        </div>
    </div>

    
    <div class="stat">
        <div class="stat-icon" style="background: #D8E6F0; color: #35607E;">
            <i class="fas fa-dna"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size: 18px; word-break: break-word;">
                <?php echo e($pet->breed ?: 'Not specified'); ?>

            </div>
            <div class="stat-label">Breed</div>
        </div>
    </div>

    
    <div class="stat">
        <div class="stat-icon" style="background: #EAE6DE; color: #4A4E4A;">
            <i class="fas <?php echo e($genderIcon); ?>"></i>
        </div>
        <div>
            <div class="stat-value">
                <?php echo e(ucfirst($pet->gender ?: 'Unknown')); ?>

            </div>
            <div class="stat-label">Gender</div>
        </div>
    </div>
</div>

<div class="row g-4">
    
    <div class="col-lg-4">

        
        <div class="section-card">
            <h3 class="section-card-title pb-3 mb-3 border-bottom d-flex align-items-center gap-2">
                <i class="fas fa-user-circle" style="color: #4A7C59;"></i> Owner Information
            </h3>
            <?php if($pet->owner): ?>
                <div class="info-row">
                    <span class="k">Name</span>
                    <span class="v">
                        <a href="<?php echo e(route('admin.pet-owners.show', $pet->owner->id)); ?>" style="color: #4A7C59; font-weight: 700; text-decoration: none;">
                            <?php echo e($pet->owner->name); ?>

                        </a>
                    </span>
                </div>
                <div class="info-row">
                    <span class="k">Phone</span>
                    <span class="v">
                        <?php if($pet->owner->phone_number): ?>
                            <a href="tel:<?php echo e($pet->owner->phone_number); ?>" style="color: #2E3230; text-decoration: none;">
                                <i class="fas fa-phone me-1 text-success"></i> <?php echo e($pet->owner->phone_number); ?>

                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="k">Email</span>
                    <span class="v">
                        <?php if($pet->owner->email): ?>
                            <a href="mailto:<?php echo e($pet->owner->email); ?>" style="color: #2E3230; text-decoration: none; word-break: break-all;">
                                <i class="fas fa-envelope me-1 text-success"></i> <?php echo e($pet->owner->email); ?>

                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="k">Registered</span>
                    <span class="v">
                        <?php echo e($pet->owner->created_at ? $pet->owner->created_at->format('M d, Y') : '—'); ?>

                    </span>
                </div>

                <a href="<?php echo e(route('admin.pet-owners.show', $pet->owner->id)); ?>"
                   class="btn w-100 rounded-pill mt-3"
                   style="background: #F5F1EA; color: #4A7C59; font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; border: 1px solid #EAE6DE; text-decoration: none;">
                    <i class="fas fa-external-link-alt me-1"></i> View Full Owner Profile
                </a>
            <?php else: ?>
                <div class="empty-note py-3">
                    <i class="fas fa-user-slash"></i>
                    <p style="margin: 0; font-size: 13px;">No registered owner profile linked to this pet.</p>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="section-card">
            <h3 class="section-card-title pb-3 mb-3 border-bottom d-flex align-items-center gap-2">
                <i class="fas fa-notes-medical" style="color: #B83230;"></i> Medical Conditions
            </h3>
            <?php if(filled($pet->medical_conditions)): ?>
                <div style="background: #FFF5F5; border: 1px solid rgba(184,50,48,0.25); border-radius: 16px; padding: 18px; color: #8A1C14; font-family: 'Nunito Sans', sans-serif;">
                    <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 12px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #B83230;">
                        <i class="fas fa-triangle-exclamation"></i> Chronic Conditions & Allergies
                    </div>
                    <div style="font-size: 14px; line-height: 1.6; white-space: pre-wrap; font-weight: 600;"><?php echo e($pet->medical_conditions); ?></div>
                </div>
            <?php else: ?>
                <div class="empty-note py-3">
                    <i class="fas fa-shield-cat"></i>
                    <p style="margin: 0; font-size: 13px;">No pre-existing conditions or allergies recorded.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    
    <div class="col-lg-8">

        
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                <h3 class="section-card-title d-flex align-items-center gap-2">
                    <i class="fas fa-file-medical" style="color: #4A7C59;"></i> Recent Medical Records
                </h3>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo e(route('admin.medical-records.create', ['pet_id' => $pet->id])); ?>"
                       class="btn btn-sm"
                       style="background: #4A7C59; color: #FFFFFF; border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px; padding: 6px 14px; text-decoration: none;">
                        <i class="fas fa-plus me-1"></i> Add Record
                    </a>
                    <a href="<?php echo e(route('admin.medical-records.pet', $pet->id)); ?>"
                       style="font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 700; color: #4A7C59; text-decoration: none;">
                        View All (<?php echo e($pet->medicalRecords->count()); ?>) <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <?php if($pet->medicalRecords->isNotEmpty()): ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Veterinarian</th>
                                <th>Diagnosis</th>
                                <th>Vaccinations</th>
                                <th>Next Visit</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $pet->medicalRecords->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="fw-bold">
                                        <?php echo e($record->consultation_date ? $record->consultation_date->format('M d, Y') : '—'); ?>

                                    </td>
                                    <td>
                                        <?php echo e($record->veterinarian ?: '—'); ?>

                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #2E3230;"><?php echo e($record->diagnosis ?: 'Routine Examination'); ?></div>
                                        <?php if($record->treatment): ?>
                                            <div style="font-size: 11px; color: #6B6358; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo e($record->treatment); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo e($record->vaccinations ?: '—'); ?>

                                    </td>
                                    <td>
                                        <?php echo e($record->next_visit_date ? $record->next_visit_date->format('M d, Y') : '—'); ?>

                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('admin.medical-records.show', $record->id)); ?>"
                                           class="btn btn-sm"
                                           style="background: #F5F1EA; color: #4A7C59; border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px; padding: 4px 12px; text-decoration: none;">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-note">
                    <i class="fas fa-file-medical"></i>
                    <p class="mb-2" style="font-weight: 600;">No medical records on file for <?php echo e($pet->name); ?>.</p>
                    <a href="<?php echo e(route('admin.medical-records.create', ['pet_id' => $pet->id])); ?>"
                       class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="fas fa-plus me-1"></i> Add First Record
                    </a>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                <h3 class="section-card-title d-flex align-items-center gap-2">
                    <i class="fas fa-calendar-check" style="color: #4A7C59;"></i> Recent Appointments
                </h3>
                <span style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; font-weight: 700; background: #FAF6F0; color: #6B6358; padding: 4px 12px; border-radius: 9999px; border: 1px solid #EAE6DE;">
                    <?php echo e($pet->appointments->count()); ?> total
                </span>
            </div>

            <?php if($pet->appointments->isNotEmpty()): ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Branch</th>
                                <th>Reason</th>
                                <th>Veterinarian</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $pet->appointments->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo e($appt->appointment_date ? $appt->appointment_date->format('M d, Y') : '—'); ?></div>
                                        <div style="font-size: 11px; color: #6B6358;">
                                            <?php echo e($appt->appointment_time ? date('g:i A', strtotime($appt->appointment_time)) : '—'); ?>

                                        </div>
                                    </td>
                                    <td>
                                        <span class="branch-chip">
                                            <i class="fas fa-location-dot"></i> <?php echo e(ucfirst($appt->branch ?: 'N/A')); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #2E3230;"><?php echo e($appt->reason ?: 'Consultation'); ?></div>
                                        <?php if($appt->notes): ?>
                                            <div style="font-size: 11px; color: #6B6358; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo e($appt->notes); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo e($appt->veterinarian ?: '—'); ?>

                                    </td>
                                    <td>
                                        <?php
                                            $st = strtolower($appt->status ?? 'pending');
                                        ?>
                                        <span class="status-pill <?php echo e($st); ?>">
                                            <?php echo e(ucfirst($st)); ?>

                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('admin.appointments.show', $appt->id)); ?>"
                                           class="btn btn-sm"
                                           style="background: #F5F1EA; color: #4A7C59; border-radius: 9999px; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 12px; padding: 4px 12px; text-decoration: none;">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-note">
                    <i class="fas fa-calendar-times"></i>
                    <p style="margin: 0; font-weight: 600;">No appointments booked for <?php echo e($pet->name); ?> yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>


<div class="modal fade" id="deletePetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <form method="POST" action="<?php echo e(route('admin.pets.destroy', $pet->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE; padding: 20px 24px;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Pet Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A; padding: 24px;">
                    <p class="mb-3">
                        Are you sure you want to permanently delete <strong><?php echo e($pet->name); ?></strong>?
                    </p>
                    <div style="background: #FFDAD8; border: 1px solid rgba(184,50,48,0.3); border-radius: 12px; padding: 12px 16px; color: #690005; font-size: 13px;">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Warning:</strong> You are deleting Pet Profiles. This removes the pet from the owner's mobile app and deletes all appointments and medical records.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE; padding: 16px 24px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal"
                            style="font-family: 'Nunito Sans', sans-serif; font-weight: 600; font-size: 14px;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4"
                            style="background: #BA1A1A; border: none; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 14px;">
                        Delete Pet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/pets/show.blade.php ENDPATH**/ ?>