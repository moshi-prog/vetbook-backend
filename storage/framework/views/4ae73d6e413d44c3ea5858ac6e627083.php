
<?php
    $items = $records instanceof \Illuminate\Pagination\AbstractPaginator ? $records->getCollection() : collect($records);
    $grouped = $items->groupBy(fn ($r) => $r->consultation_date->format('F Y'));
    $today = \Carbon\Carbon::today();
?>

<?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="tl-month"><?php echo e($month); ?></div>
    <div class="tl">
        <?php $__currentLoopData = $group; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $pet = $r->pet;
                $seenAt = $pet ? $pet->appointments->pluck('branch')->filter()->unique()->values() : collect();
                $vaxList = $r->vaccinations
                    ? array_values(array_filter(array_map('trim', preg_split('/[,;\n\r]+/', $r->vaccinations))))
                    : [];
            ?>
            <div class="tl-item">
                <div class="tl-date">
                    <div class="d"><?php echo e($r->consultation_date->format('d')); ?></div>
                    <div class="m"><?php echo e($r->consultation_date->format('M')); ?></div>
                </div>
                <div class="tl-card" onclick="window.location='<?php echo e(route('admin.medical-records.show', $r->id)); ?>'">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="selected_ids[]" value="<?php echo e($r->id); ?>" class="row-checkbox"
                                   style="cursor: pointer; width: 18px; height: 18px; accent-color: #4A7C59; flex-shrink: 0;"
                                   title="Select record" onclick="event.stopPropagation()">
                            <?php if($showPet && $pet): ?>
                                <?php echo $__env->make('admin.medical-records._avatar', ['pet' => $pet, 'size' => 44], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <div>
                                    <a href="<?php echo e(route('admin.medical-records.pet', $pet->id)); ?>" class="tl-pet-name"
                                       onclick="event.stopPropagation()"><?php echo e($pet->name); ?></a>
                                    <div class="tl-sub">
                                        <?php echo e($pet->species); ?><?php echo e($pet->breed ? ' · ' . $pet->breed : ''); ?>

                                        &middot; Owner: <?php echo e($pet->owner->name ?? 'Unknown'); ?>

                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="tl-sub" style="font-size: 13px; color: #2E3230; font-weight: 600;">
                                    <?php echo e($r->consultation_date->format('l, F j, Y')); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if(!empty($r->veterinarian) && $r->veterinarian !== 'Clinic Staff'): ?>
                            <div class="tl-sub text-end">
                                <i class="fas fa-user-doctor me-1" style="color: #4A7C59;"></i><?php echo e($r->veterinarian); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tl-diagnosis">
                        <span class="lbl">Diagnosis</span>
                        <?php echo e($r->diagnosis ? \Illuminate\Support\Str::limit($r->diagnosis, 150) : 'No diagnosis recorded'); ?>

                    </div>

                    <?php if(count($vaxList)): ?>
                        <div class="mb-2">
                            <?php $__currentLoopData = array_slice($vaxList, 0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="vax-pill ok" style="font-size: 11px; padding: 3px 10px; margin-bottom: 4px;">
                                    <i class="fas fa-syringe"></i><?php echo e($vx); ?>

                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(count($vaxList) > 4): ?>
                                <span class="tl-sub">+<?php echo e(count($vaxList) - 4); ?> more</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="tl-foot">
                        <div>
                            <?php if($r->next_visit_date): ?>
                                <span class="next-pill <?php echo e($r->next_visit_date->gte($today) ? 'upcoming' : 'past'); ?>">
                                    <i class="fas fa-calendar-day"></i>
                                    Next visit <?php echo e($r->next_visit_date->format('M d, Y')); ?>

                                </span>
                            <?php endif; ?>
                            <?php if($showPet): ?>
                                <?php $__currentLoopData = $seenAt; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="branch-chip <?php echo e(($branch ?? null) && $b === $branch ? 'mine' : ''); ?>">
                                        <i class="fas fa-location-dot"></i><?php echo e(\Illuminate\Support\Str::before($b, ' Branch')); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?php echo e(route('admin.medical-records.show', $r->id)); ?>" class="tl-link"
                               onclick="event.stopPropagation()">View details <i class="fas fa-arrow-right ms-1"></i></a>
                            <button type="button" class="btn btn-sm text-danger p-1"
                                    title="Delete record"
                                    onclick="event.stopPropagation();"
                                    data-bs-toggle="modal" data-bs-target="#deleteMrModal-<?php echo e($r->id); ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade text-start" id="deleteMrModal-<?php echo e($r->id); ?>" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation();">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border-radius: 24px; border: none;">
                                <form method="POST" action="<?php echo e(route('admin.medical-records.destroy', $r->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <input type="hidden" name="redirect_to" value="<?php echo e($showPet ? 'index' : 'pet'); ?>">
                                    <div class="modal-header" style="border-bottom: 1px solid #EAE6DE;">
                                        <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                                            <i class="fas fa-trash-alt me-2"></i> Delete Medical Record
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A;">
                                        <p class="mb-2">Are you sure you want to delete this medical record from <strong><?php echo e($r->consultation_date->format('M d, Y')); ?></strong>?</p>
                                        <div class="p-2 rounded mt-2" style="background: #E8F5E9; border: 1px solid rgba(46,125,50,0.25); color: #1B5E20; font-size: 12px;">
                                            <i class="fas fa-shield-alt me-1"></i> <strong>Safe Deletion:</strong> The pet profile and appointments will remain completely untouched.
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="border-top: 1px solid #EAE6DE;">
                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger rounded-pill px-4" style="background: #BA1A1A; border: none;">
                                            Delete Record
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/medical-records/_timeline.blade.php ENDPATH**/ ?>