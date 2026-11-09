<?php $__env->startSection('title', 'Settings'); ?>

<?php $__env->startSection('styles'); ?>
<style>
    .settings-header {
        margin-bottom: 24px;
    }
    .settings-title {
        font-family: 'Literata', serif;
        font-size: 28px;
        font-weight: 700;
        color: #2E3230;
        margin-bottom: 4px;
    }
    .settings-subtitle {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #6B6358;
        margin: 0;
    }

    /* Tabs Bar */
    .settings-nav {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        background: #FFFFFF;
        padding: 12px;
        border-radius: 9999px;
        border: 1px solid rgba(196,200,188,0.3);
        box-shadow: 0 4px 20px rgba(46,50,48,0.04);
    }
    .settings-tab-btn {
        padding: 10px 20px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .settings-tab-btn:hover {
        background: #EAE6DE;
        color: #2E3230;
    }
    .settings-tab-btn.active {
        background: #4A7C59;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(74,124,89,0.25);
    }

    /* Tab Panes */
    .tab-pane {
        display: none;
    }
    .tab-pane.active {
        display: block;
        animation: fadeIn 0.25s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Cards & Layout */
    .settings-card {
        background: #FFFFFF;
        border: 1px solid rgba(196,200,188,0.3);
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(46,50,48,0.05);
        margin-bottom: 24px;
    }
    .section-head {
        border-bottom: 1px solid #EAE6DE;
        padding-bottom: 16px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .section-title {
        font-family: 'Literata', serif;
        font-size: 20px;
        font-weight: 700;
        color: #2E3230;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-desc {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        color: #6B6358;
        margin-top: 4px;
    }

    /* Form Inputs */
    .f-label {
        display: block;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: #6B6358;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 8px;
    }
    .f-input, .f-select, .f-textarea {
        width: 100%;
        padding: 12px 18px;
        border-radius: 16px;
        border: 1px solid rgba(196,200,188,0.5);
        background: #FAF6F0;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        color: #2E3230;
        transition: all 0.15s ease;
    }
    .f-input:focus, .f-select:focus, .f-textarea:focus {
        outline: none;
        border-color: #4A7C59;
        background: #FFFFFF;
        box-shadow: 0 0 0 3px rgba(74,124,89,0.12);
    }
    .f-help {
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        color: #6B6358;
        margin-top: 6px;
    }

    /* Buttons */
    .btn-green-save {
        padding: 12px 28px;
        border-radius: 9999px;
        background: #4A7C59;
        color: #FFFFFF;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-green-save:hover {
        background: #3d6b4a;
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(74,124,89,0.25);
    }
    .btn-soft {
        padding: 10px 20px;
        border-radius: 9999px;
        background: #F5F1EA;
        color: #4A4E4A;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-soft:hover {
        background: #EAE6DE;
        color: #2E3230;
    }

    /* Badge & Tag */
    .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 9999px;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 12px;
        font-weight: 700;
    }
    .badge-pill.active { background: #C8E8D0; color: #2A6038; }
    .badge-pill.warning { background: #F8E0A8; color: #705C30; }
    .badge-pill.neutral { background: #EAE6DE; color: #4A4E4A; }

    /* Custom Switch Toggle */
    .switch-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        background: #FAF6F0;
        border-radius: 16px;
        margin-bottom: 12px;
    }
    .form-check-input:checked {
        background-color: #4A7C59;
        border-color: #4A7C59;
    }

    /* Branch & List Items */
    .branch-item-card {
        background: #FAF6F0;
        border: 1px solid rgba(196,200,188,0.4);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }
    .add-branch-dashed {
        border: 2px dashed rgba(74,124,89,0.35);
        border-radius: 20px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        background: #FAF6F0;
        color: #4A7C59;
        font-family: 'Nunito Sans', sans-serif;
        font-size: 14px;
        font-weight: 700;
        transition: all 0.2s;
    }
    .add-branch-dashed:hover {
        background: #F0ECE4;
        border-color: #4A7C59;
    }

    /* FAQ accordion item */
    .faq-box {
        background: #FAF6F0;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 12px;
    }

    /* Admin user item */
    .admin-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
        border-bottom: 1px solid #EAE6DE;
        flex-wrap: wrap;
        gap: 12px;
    }
    .admin-item-row:last-child {
        border-bottom: none;
    }
    .user-avatar-initial {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #C8E8D0;
        color: #2A6038;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Literata', serif;
        font-weight: 700;
        font-size: 18px;
    }

    /* Danger zone */
    .danger-zone-card {
        border: 1px solid rgba(184,50,48,0.3);
        background: #FFF5F5;
        border-radius: 24px;
        padding: 24px;
        margin-top: 24px;
    }
    /* Cropper modal custom styling */
    .cropper-view-box,
    .cropper-face {
        border-radius: 50%;
    }
    .cropper-view-box {
        outline: 0;
        box-shadow: 0 0 0 2px #4A7C59;
    }
    .cropper-dashed {
        border: 1px dashed rgba(255, 255, 255, 0.6);
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="settings-header">
    <h2 class="settings-title">Settings</h2>
    <p class="settings-subtitle">Manage clinic profile, branch locations, AI assistant, SMS alerts, and system parameters.</p>
</div>


<div class="settings-nav">
    <button type="button" class="settings-tab-btn active" data-tab="clinic">
        <i class="fas fa-clinic-medical"></i> Clinic Information
    </button>
    <button type="button" class="settings-tab-btn" data-tab="branches">
        <i class="fas fa-code-branch"></i> Branches
    </button>
    <button type="button" class="settings-tab-btn" data-tab="chatbot">
        <i class="fas fa-robot"></i> AI Chatbot
    </button>
    <button type="button" class="settings-tab-btn" data-tab="sms">
        <i class="fas fa-comment-sms"></i> SMS Templates
    </button>
    <button type="button" class="settings-tab-btn" data-tab="admins">
        <i class="fas fa-users-gear"></i> Admin Users
    </button>
    <button type="button" class="settings-tab-btn" data-tab="preferences">
        <i class="fas fa-user-gear"></i> Preferences &amp; Account
    </button>
</div>




<div class="tab-pane active" id="tab-clinic">
    <form method="POST" action="<?php echo e(route('admin.settings.clinic')); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-hospital text-success"></i> Clinic Profile</h3>
                    <div class="section-desc">General identity, primary hotline, and official operating schedule.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="f-label">Clinic Name</label>
                    <input type="text" name="clinic_name" class="f-input" value="<?php echo e($clinic['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Tagline / Mission</label>
                    <input type="text" name="tagline" class="f-input" value="<?php echo e($clinic['tagline']); ?>">
                </div>

                <div class="col-md-6">
                    <label class="f-label">Primary Contact Email</label>
                    <input type="email" name="email" class="f-input" value="<?php echo e($clinic['email']); ?>" required>
                </div>

                <?php if(auth()->user()->branch === null): ?>
                    
                    <div class="col-md-6">
                        <label class="f-label">Mabini Branch Support Hotline</label>
                        <input type="text" name="branch_mabini_phone" class="f-input" value="<?php echo e(\App\Models\ClinicSetting::get('branch_mabini_phone', '0917-123-4567')); ?>" required maxlength="30">
                        <div class="f-help">Direct mobile dialer hotline for Mabini Branch.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="f-label">Batangas City Branch Support Hotline</label>
                        <input type="text" name="branch_batangas_phone" class="f-input" value="<?php echo e(\App\Models\ClinicSetting::get('branch_batangas_phone', '0918-765-4321')); ?>" required maxlength="30">
                        <div class="f-help">Direct mobile dialer hotline for Batangas City Branch.</div>
                    </div>
                    <input type="hidden" name="phone" value="<?php echo e($clinic['phone']); ?>">
                <?php else: ?>
                    <input type="hidden" name="phone" value="<?php echo e($clinic['phone']); ?>">
                <?php endif; ?>

                <div class="col-md-6">
                    <label class="f-label">Accreditation / License No.</label>
                    <input type="text" name="accreditation" class="f-input" value="<?php echo e($clinic['accreditation']); ?>">
                </div>

                <div class="col-md-6">
                    <label class="f-label">Clinic Logo</label>
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm overflow-hidden" style="width: 56px; height: 56px; color: #4A7C59; flex-shrink: 0; border: 2px solid #EAE6DE;">
                            <?php if(!empty($clinic['logo'])): ?>
                                <img id="clinicLogoPreviewImg" src="<?php echo e(str_starts_with($clinic['logo'], 'http') ? $clinic['logo'] : asset('storage/' . ltrim($clinic['logo'], '/'))); ?>" alt="Clinic Logo" style="width: 100%; height: 100%; object-fit: cover;">
                                <i id="clinicLogoPawIcon" class="fas fa-paw fs-4" style="display: none;"></i>
                            <?php else: ?>
                                <img id="clinicLogoPreviewImg" src="" alt="Clinic Logo" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <i id="clinicLogoPawIcon" class="fas fa-paw fs-4"></i>
                            <?php endif; ?>
                        </div>

                        <?php if(auth()->user()->branch === null): ?>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn-soft d-inline-flex align-items-center gap-1" id="triggerLogoPickerBtn">
                                        <i class="fas fa-crop-simple"></i> <span>Choose &amp; Crop Logo</span>
                                    </button>
                                    <input type="file" id="logoFileInput" accept="image/*" class="d-none">
                                    <input type="hidden" name="cropped_logo" id="croppedLogoInput" value="">
                                </div>
                                <div class="f-help mt-1" id="logoStatusText">PNG, JPG, WEBP up to 2MB. 1:1 circle crop.</div>
                            </div>
                        <?php else: ?>
                            
                            <div class="flex-grow-1">
                                <span class="badge-pill neutral"><i class="fas fa-lock me-1"></i> Read-Only</span>
                                <div class="f-help mt-1">Official clinic logo &amp; branding are managed exclusively by Super Admin.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(auth()->user()->branch !== null): ?>
                <hr class="my-4" style="border-color: #EAE6DE;">

                <h4 class="section-title mb-3" style="font-size: 16px;"><i class="fas fa-clock text-success"></i> <?php echo e(auth()->user()->branch); ?> Operating Schedule</h4>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="f-label">Monday – Friday</label>
                        <input type="text" name="hours_weekday" class="f-input" value="<?php echo e($clinic['hours_weekday']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="f-label">Saturday</label>
                        <input type="text" name="hours_saturday" class="f-input" value="<?php echo e($clinic['hours_saturday']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="f-label">Sunday</label>
                        <input type="text" name="hours_sunday" class="f-input" value="<?php echo e($clinic['hours_sunday']); ?>">
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>




<div class="tab-pane" id="tab-branches">
    <div class="settings-card">
        <div class="section-head">
            <div>
                <h3 class="section-title"><i class="fas fa-location-dot text-success"></i> Clinic Branches</h3>
                <div class="section-desc">Manage physical clinic stations in Mabini and Batangas City.</div>
            </div>
            <button type="button" class="btn-green-save" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="fas fa-plus"></i> Add New Branch
            </button>
        </div>

        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="branch-item-card">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="m-0" style="font-family: 'Literata', serif; font-size: 18px; font-weight: 700; color: #2E3230;">
                            <?php echo e($branch['name']); ?>

                        </h4>
                        <span class="badge-pill active"><i class="fas fa-circle-check"></i> Active</span>
                    </div>
                    <div class="text-muted small mb-2"><i class="fas fa-map-pin me-1 text-success"></i> <?php echo e($branch['address']); ?></div>
                    <div class="small text-secondary mb-1"><i class="fas fa-phone me-1"></i> <?php echo e($branch['phone']); ?></div>
                    <div class="small text-secondary"><i class="fas fa-clock me-1"></i> <?php echo e($branch['hours']); ?></div>
                </div>
                <div class="d-flex gap-2">
                    <?php if(auth()->user()->branch === null): ?>
                    <button type="button" class="btn-soft" data-bs-toggle="modal" data-bs-target="#editBranchModal-<?php echo e($branch['key']); ?>">
                        <i class="fas fa-pen"></i> Edit
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn-soft text-danger" onclick="confirm('Are you sure you want to deactivate <?php echo e($branch['name']); ?>?')">
                        <i class="fas fa-power-off"></i> Deactivate
                    </button>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="add-branch-dashed" data-bs-toggle="modal" data-bs-target="#addBranchModal">
            <i class="fas fa-plus-circle me-1"></i> Add Another Clinic Branch Location
        </div>
    </div>
</div>




<div class="tab-pane" id="tab-chatbot">
    <form method="POST" action="<?php echo e(route('admin.settings.chatbot')); ?>">
        <?php echo csrf_field(); ?>
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-brain text-success"></i> VetBook AI Assistant</h3>
                    <div class="section-desc">Control assistant instructions, guardrails, and quick answers.</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="f-label">AI Engine Provider</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fa-brands fa-google text-primary me-2"></i> <?php echo e($chatbot['provider']); ?></span>
                        <span class="badge-pill active">Connected</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="f-label">Foundation Model</label>
                    <div class="f-input bg-white">
                        <i class="fas fa-microchip text-success me-2"></i> <?php echo e($chatbot['model']); ?>

                    </div>
                </div>
                <div class="col-md-4">
                    <label class="f-label">Assistant Status</label>
                    <div class="switch-wrap bg-white border">
                        <span class="small fw-bold">Active for Pet Owners</span>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" checked>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="f-label">System Instruction &amp; Clinic Guardrails</label>
                <textarea name="system_prompt" rows="5" class="f-textarea" required><?php echo e($chatbot['system_prompt']); ?></textarea>
                <div class="f-help">Instructs Gemini how to respond, adhere to clinic hours, and refuse veterinary prescriptions.</div>
            </div>

            <hr class="my-4" style="border-color: #EAE6DE;">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="section-title" style="font-size: 16px;"><i class="fas fa-comments text-success"></i> Structured Q&amp;A Knowledge Base</h4>
                <button type="button" class="btn-soft" onclick="addNewFaq()"><i class="fas fa-plus"></i> Add FAQ</button>
            </div>

            <div id="faqContainer">
                <?php $__currentLoopData = $chatbot['faqs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="faq-box" id="faq-<?php echo e($index); ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge-pill neutral">FAQ #<?php echo e($index + 1); ?></span>
                            <button type="button" class="btn btn-sm text-danger p-0" onclick="document.getElementById('faq-<?php echo e($index); ?>').remove()">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                        <input type="text" class="f-input mb-2" value="<?php echo e($faq['question']); ?>" placeholder="Question">
                        <textarea rows="2" class="f-textarea" placeholder="Answer"><?php echo e($faq['answer']); ?></textarea>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>




<div class="tab-pane" id="tab-sms">
    <form method="POST" action="<?php echo e(route('admin.settings.sms')); ?>">
        <?php echo csrf_field(); ?>
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-paper-plane text-success"></i> SMS Notification Templates</h3>
                    <div class="section-desc">Automated transactional SMS dispatches to Philippine mobile numbers.</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="f-label">Gateway Provider</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fas fa-tower-broadcast text-success me-2"></i> <?php echo e($sms['provider']); ?></span>
                        <span class="badge-pill warning"><i class="fas fa-circle-exclamation"></i> Ready for API Key</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Automated Delivery</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span class="small fw-bold text-success"><i class="fas fa-circle-check me-1"></i> Live Automated Delivery</span>
                        <span class="badge-pill success">Always Active</span>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <?php $__currentLoopData = $sms['templates']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tpl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small"><?php echo e($tpl['name']); ?></span>
                                <span class="badge-pill neutral" id="counter-<?php echo e($tpl['id']); ?>"><?php echo e(strlen($tpl['content'])); ?>/160 chars</span>
                            </div>
                            <textarea rows="3" class="f-textarea mb-2" name="templates[<?php echo e($tpl['id']); ?>]" id="text-<?php echo e($tpl['id']); ?>" oninput="updateCharCount('<?php echo e($tpl['id']); ?>', this)"><?php echo e($tpl['content']); ?></textarea>
                            <div class="small text-muted" style="font-size: 11px;">
                                Placeholders: <code>{owner_name}</code>, <code>{pet_name}</code>, <code>{date}</code>, <code>{time}</code>, <code>{branch}</code>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>




<div class="tab-pane" id="tab-admins">
    <div class="settings-card">
        <div class="section-head">
            <div>
                <h3 class="section-title"><i class="fas fa-user-shield text-success"></i> Admin Staff Accounts</h3>
                <div class="section-desc">Manage authenticated personnel and assign branch authorizations.</div>
            </div>
            <button type="button" class="btn-green-save" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-user-plus"></i> Add Admin User
            </button>
        </div>

        <div class="mb-3">
            <?php $__currentLoopData = $admins ?? $adminUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="admin-item-row">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-initial">
                            <?php echo e(strtoupper(substr($admin->name, 0, 1))); ?>

                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?php echo e($admin->name); ?></div>
                            <div class="text-muted small"><?php echo e($admin->email); ?></div>
                            <div class="small text-success mt-1"><i class="fas fa-phone me-1"></i> <?php echo e($admin->phone_number ?: 'No phone set'); ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-pill <?php echo e($admin->branch ? 'active' : 'neutral'); ?>">
                            <i class="fas fa-location-dot"></i> <?php echo e($admin->branch ?? 'All Branches (Super Admin)'); ?>

                        </span>
                        <span class="badge-pill" style="background: #E8F0FE; color: #1967D2;">
                            <i class="fas fa-user-shield"></i> <?php echo e(ucfirst($admin->role)); ?>

                        </span>
                    </div>
                    <div class="text-muted small">
                        Created <?php echo e($admin->created_at ? $admin->created_at->format('M d, Y') : 'System Seed'); ?>

                    </div>
                    <div class="d-flex gap-2">
                        <?php if(auth()->user()->branch === null): ?>
                            <button type="button" class="btn-soft text-success" data-bs-toggle="modal" data-bs-target="#editAdminModal-<?php echo e($admin->id); ?>">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                            <button type="button" class="btn-soft text-primary" data-bs-toggle="modal" data-bs-target="#resetAdminModal-<?php echo e($admin->id); ?>">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>




<div class="tab-pane" id="tab-preferences">
    
    <form method="POST" action="<?php echo e(route('admin.profile.update')); ?>">
        <?php echo csrf_field(); ?>
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-user-circle text-success"></i> My Admin Account</h3>
                    <div class="section-desc">Manage your profile name, administrative email address, and station assignment.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="f-label">Full Name</label>
                    <input type="text" name="name" class="f-input <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name', auth()->user()->name)); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Email Address</label>
                    <input type="email" name="email" class="f-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email', auth()->user()->email)); ?>" required>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-6">
                    <label class="f-label">Personal Admin Mobile Number</label>
                    <input type="text" name="phone_number" class="f-input <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('phone_number', auth()->user()->phone_number)); ?>" placeholder="e.g. 09171234567">
                    <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="col-md-6">
                    <label class="f-label">Branch Assignment</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fas fa-location-dot text-success me-2"></i> <?php echo e(auth()->user()->branch ?? 'All Branches'); ?></span>
                        <span class="badge-pill <?php echo e(auth()->user()->branch ? 'active' : 'neutral'); ?>">
                            <?php echo e(auth()->user()->branch ? 'Branch Admin' : 'Super Admin'); ?>

                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="f-label">System Role</label>
                    <div class="f-input d-flex align-items-center justify-content-between bg-white">
                        <span><i class="fas fa-shield-halved text-success me-2"></i> Administrator</span>
                        <span class="badge-pill active"><i class="fas fa-circle-check"></i> Active Staff</span>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Details</button>
            </div>
        </div>
    </form>

    
    <form method="POST" action="<?php echo e(route('admin.profile.password')); ?>">
        <?php echo csrf_field(); ?>
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-key text-success"></i> Change Password</h3>
                    <div class="section-desc">Ensure your administrative access is protected with a secure and unique password.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="f-label">Current Password</label>
                    <input type="password" name="current_password" class="f-input <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="••••••••" required>
                    <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label class="f-label">New Password</label>
                    <input type="password" name="new_password" class="f-input <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Min. 6 characters" minlength="6" required>
                    <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label class="f-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" class="f-input" placeholder="Repeat new password" minlength="6" required>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-lock"></i> Update Password</button>
            </div>
        </div>
    </form>

    
    <form method="POST" action="<?php echo e(route('admin.settings.preferences')); ?>">
        <?php echo csrf_field(); ?>
        <div class="settings-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><i class="fas fa-gears text-success"></i> Regional &amp; System Preferences</h3>
                    <div class="section-desc">Locale standards, session timeouts, and security triggers.</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="f-label">System Timezone</label>
                    <select name="timezone" class="f-select">
                        <option value="Asia/Manila" selected>Asia/Manila (GMT+8:00)</option>
                        <option value="UTC">UTC (Universal Time)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="f-label">Standard Date Format</label>
                    <select name="date_format" class="f-select">
                        <option value="F j, Y" selected>October 13, 2026 (Month D, YYYY)</option>
                        <option value="m/d/Y">10/13/2026 (MM/DD/YYYY)</option>
                        <option value="d/m/Y">13/10/2026 (DD/MM/YYYY)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="f-label">Display Time Format</label>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="time_format" id="t12" value="12" checked>
                            <label class="form-check-label" for="t12">12-Hour (e.g. 02:30 PM)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="time_format" id="t24" value="24">
                            <label class="form-check-label" for="t24">24-Hour (e.g. 14:30)</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="f-label">Session Inactivity Timeout</label>
                    <select name="session_timeout" class="f-select">
                        <option value="30">30 minutes</option>
                        <option value="60" selected>1 hour</option>
                        <option value="240">4 hours</option>
                        <option value="1440">24 hours</option>
                    </select>
                </div>
            </div>

            <hr class="my-4" style="border-color: #EAE6DE;">

            <h4 class="section-title mb-3" style="font-size: 16px;"><i class="fas fa-bell text-success"></i> Notification Channels</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="switch-wrap">
                        <div>
                            <div class="fw-bold small text-dark">Email Alerts</div>
                            <div class="text-muted" style="font-size: 11px;">Send appointment receipts via mail</div>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" checked>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="danger-zone-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-bold text-danger"><i class="fas fa-triangle-exclamation me-1"></i> Maintenance Mode</div>
                        <div class="text-muted small">When active, pet owners cannot place bookings via the mobile app. Only admins can access.</div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch">
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>


<div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Add Clinic Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="f-label">Branch Name</label>
                    <input type="text" class="f-input" placeholder="e.g. Lipa City Branch">
                </div>
                <div class="mb-3">
                    <label class="f-label">Complete Street Address</label>
                    <input type="text" class="f-input" placeholder="Building, Street, Barangay">
                </div>
                <div class="mb-3">
                    <label class="f-label">Contact Number</label>
                    <input type="text" class="f-input" placeholder="+63 9...">
                </div>
                <div class="mb-3">
                    <label class="f-label">Operating Hours</label>
                    <input type="text" class="f-input" placeholder="Mon-Sat: 9AM-6PM">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-green-save" data-bs-dismiss="modal" onclick="alert('Branch registered in pilot registry!')">Create Branch</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Add Staff Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="f-label">Full Name</label>
                    <input type="text" class="f-input" placeholder="e.g. Dr. Juan Dela Cruz">
                </div>
                <div class="mb-3">
                    <label class="f-label">Email Address</label>
                    <input type="email" class="f-input" placeholder="user@vetbook.com">
                </div>
                <div class="mb-3">
                    <label class="f-label">Branch Assignment</label>
                    <select class="f-select">
                        <option value="Mabini Branch">Mabini Branch</option>
                        <option value="Batangas City Branch">Batangas City Branch</option>
                        <option value="">All Branches (Super Admin)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="f-label">Temporary Password</label>
                    <input type="password" class="f-input" placeholder="••••••••">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-green-save" data-bs-dismiss="modal" onclick="alert('Admin invitation link ready.')">Create Account</button>
            </div>
        </div>
    </div>
</div>


<?php if(auth()->user()->branch === null): ?>
    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="modal fade" id="editBranchModal-<?php echo e($branch['key']); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
                    <form method="POST" action="<?php echo e(route('admin.settings.branch', $branch['key'])); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Edit <?php echo e($branch['name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="f-label">Branch Hotline</label>
                            <input type="text" name="phone" class="f-input" value="<?php echo e($branch['phone']); ?>" required maxlength="30">
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-green-save">Save Branch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<?php if(auth()->user()->branch === null): ?>
    <?php $__currentLoopData = $admins ?? $adminUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        
        <div class="modal fade" id="editAdminModal-<?php echo e($admin->id); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
                    <form method="POST" action="<?php echo e(route('admin.users.update', $admin->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Edit Admin Details</h5>
                                <p class="text-muted small mb-0">Update station, phone, and profile for <strong><?php echo e($admin->name); ?></strong></p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="mb-3">
                                <label class="f-label">Full Name</label>
                                <input type="text" name="name" class="f-input" value="<?php echo e($admin->name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Email Address</label>
                                <input type="email" name="email" class="f-input" value="<?php echo e($admin->email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Personal Mobile Number</label>
                                <input type="text" name="phone_number" class="f-input" value="<?php echo e($admin->phone_number); ?>" placeholder="e.g. 09171234567">
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Branch Station Assignment</label>
                                <select name="branch" class="f-select">
                                    <option value="" <?php echo e(!$admin->branch ? 'selected' : ''); ?>>All Branches (Super Admin)</option>
                                    <option value="Mabini Branch" <?php echo e($admin->branch === 'Mabini Branch' ? 'selected' : ''); ?>>Mabini Branch</option>
                                    <option value="Batangas City Branch" <?php echo e($admin->branch === 'Batangas City Branch' ? 'selected' : ''); ?>>Batangas City Branch</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-green-save"><i class="fas fa-check"></i> Save Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="resetAdminModal-<?php echo e($admin->id); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 24px; border: none; padding: 12px;">
                    <form method="POST" action="<?php echo e(route('admin.users.reset-password', $admin->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold" style="font-family: 'Literata', serif;">Reset Admin Password</h5>
                                <p class="text-muted small mb-0">Set a new password for <strong><?php echo e($admin->name); ?></strong> (<?php echo e($admin->email); ?>)</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="mb-3">
                                <label class="f-label">New Password</label>
                                <input type="password" name="new_password" class="f-input" placeholder="Minimum 6 characters" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="f-label">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation" class="f-input" placeholder="Repeat new password" minlength="6" required>
                            </div>
                            <div class="p-3 rounded-4 small" style="background: #FAF6F0; border: 1px solid rgba(196,200,188,0.4); color: #6B6358;">
                                <i class="fas fa-circle-info text-success me-1"></i> The administrator will be able to log in immediately using this new password.
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn-green-save"><i class="fas fa-key"></i> Set New Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<?php if(auth()->user()->branch === null): ?>
    <!-- Modal: Interactive 1:1 Clinic Logo Cropper -->
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 16px 48px rgba(0,0,0,0.18); overflow: hidden;">
                <div class="modal-header border-0 pb-0" style="padding: 24px 28px 12px;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #C8E8D0; color: #4A7C59;">
                            <i class="fas fa-crop-simple fs-6"></i>
                        </span>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="cropperModalLabel" style="font-family: 'Literata', serif; color: #2E3230;">Crop Clinic Logo</h5>
                            <p class="text-muted small mb-0">Adjust position, zoom, and orientation for a clean 1:1 circular avatar.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 20px 28px;">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-8">
                            <div style="width: 100%; height: 360px; background-color: #1A1A1A; border-radius: 16px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <img id="cropperImageSrc" src="" alt="To Crop" style="max-width: 100%; max-height: 100%; display: block;">
                            </div>
                            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropZoomInBtn">
                                    <i class="fas fa-magnifying-glass-plus me-1"></i> Zoom In
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropZoomOutBtn">
                                    <i class="fas fa-magnifying-glass-minus me-1"></i> Zoom Out
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropRotateLeftBtn">
                                    <i class="fas fa-rotate-left me-1"></i> Rotate
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropResetBtn">
                                    <i class="fas fa-arrow-rotate-right me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 bg-light rounded-4 border">
                                <div class="small fw-bold text-uppercase text-muted mb-2">Circle Preview</div>
                                <div id="cropPreviewContainer" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; margin: 0 auto 12px; border: 3px solid #4A7C59; box-shadow: 0 4px 14px rgba(74,124,89,0.25); background: #FAF6F0;">
                                </div>
                                <div class="small text-muted" style="font-size: 11px;">
                                    This circular avatar will display across the Admin Sidebar, Mobile Home header, and Mobile About sheet.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0" style="padding: 12px 28px 24px;">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-green-save px-4" id="applyCropBtn">
                        <i class="fas fa-check me-1"></i> Crop &amp; Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Vanilla JS Tab Switching with Hash & Validation Error Support
        const tabButtons = document.querySelectorAll('.settings-tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        function activateTab(targetTab) {
            tabButtons.forEach(btn => {
                if (btn.getAttribute('data-tab') === targetTab) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            tabPanes.forEach(pane => {
                if (pane.id === 'tab-' + targetTab) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
        }

        tabButtons.forEach(button => {
            button.addEventListener('click', function () {
                const targetTab = this.getAttribute('data-tab');
                activateTab(targetTab);
                if (history.pushState) {
                    history.pushState(null, null, '#' + targetTab);
                } else {
                    window.location.hash = targetTab;
                }
            });
        });

        // Check URL hash on page load
        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById('tab-' + hash)) {
            activateTab(hash);
        }
        <?php if($errors->has('current_password') || $errors->has('new_password') || $errors->has('name') || $errors->has('email')): ?>
            activateTab('preferences');
        <?php endif; ?>
        <?php if($errors->has('templates') || $errors->has('templates.*')): ?>
            activateTab('sms');
        <?php endif; ?>
    });

    // SMS Character counter
    function updateCharCount(id, textarea) {
        const counter = document.getElementById('counter-' + id);
        if (counter) {
            counter.innerText = textarea.value.length + '/160 chars';
        }
    }

    // Dynamic FAQ adding
    function addNewFaq() {
        const container = document.getElementById('faqContainer');
        const count = container.children.length + 1;
        const div = document.createElement('div');
        div.className = 'faq-box';
        div.id = 'faq-' + count;
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge-pill neutral">FAQ #${count}</span>
                <button type="button" class="btn btn-sm text-danger p-0" onclick="this.closest('.faq-box').remove()">
                    <i class="fas fa-trash-can"></i>
                </button>
            </div>
            <input type="text" class="f-input mb-2" placeholder="Question">
            <textarea rows="2" class="f-textarea" placeholder="Answer"></textarea>
        `;
        container.appendChild(div);
    }
</script>
<?php if(auth()->user()->branch === null): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let cropper = null;
        const logoFileInput = document.getElementById('logoFileInput');
        const triggerLogoPickerBtn = document.getElementById('triggerLogoPickerBtn');
        const cropperModalEl = document.getElementById('cropperModal');
        let cropperModalInstance = null;
        const cropperImageSrc = document.getElementById('cropperImageSrc');
        const applyCropBtn = document.getElementById('applyCropBtn');
        const croppedLogoInput = document.getElementById('croppedLogoInput');

        if (triggerLogoPickerBtn && logoFileInput && cropperModalEl) {
            triggerLogoPickerBtn.addEventListener('click', function () {
                logoFileInput.value = '';
                logoFileInput.click();
            });

            logoFileInput.addEventListener('change', function (e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    if (!file.type.match(/^image\//)) {
                        alert('Please select a valid image file (PNG, JPG, WEBP).');
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        alert('Image size exceeds 10MB. Please choose a smaller image.');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        cropperImageSrc.src = evt.target.result;

                        if (!cropperModalInstance) {
                            cropperModalInstance = new bootstrap.Modal(cropperModalEl, {
                                backdrop: 'static',
                                keyboard: false
                            });
                        }
                        cropperModalInstance.show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            cropperModalEl.addEventListener('shown.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                }
                if (typeof Cropper !== 'undefined') {
                    cropper = new Cropper(cropperImageSrc, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.95,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        preview: '#cropPreviewContainer'
                    });
                }
            });

            cropperModalEl.addEventListener('hidden.bs.modal', function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropperImageSrc.src = '';
            });

            document.getElementById('cropZoomInBtn')?.addEventListener('click', function () {
                cropper?.zoom(0.1);
            });
            document.getElementById('cropZoomOutBtn')?.addEventListener('click', function () {
                cropper?.zoom(-0.1);
            });
            document.getElementById('cropRotateLeftBtn')?.addEventListener('click', function () {
                cropper?.rotate(-90);
            });
            document.getElementById('cropResetBtn')?.addEventListener('click', function () {
                cropper?.reset();
            });

            applyCropBtn?.addEventListener('click', function () {
                if (!cropper) return;

                const canvas = cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                if (!canvas) {
                    alert('Could not process cropped image.');
                    return;
                }

                const croppedDataUrl = canvas.toDataURL('image/png');
                if (croppedLogoInput) {
                    croppedLogoInput.value = croppedDataUrl;
                }

                const previewImg = document.getElementById('clinicLogoPreviewImg');
                if (previewImg) {
                    previewImg.src = croppedDataUrl;
                    previewImg.style.display = 'block';
                }
                const pawIcon = document.getElementById('clinicLogoPawIcon');
                if (pawIcon) {
                    pawIcon.style.display = 'none';
                }

                const statusText = document.getElementById('logoStatusText');
                if (statusText) {
                    statusText.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Cropped logo ready! Click "Save Changes" below.</span>';
                }

                if (cropperModalInstance) {
                    cropperModalInstance.hide();
                }
            });
        }
    });
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/settings/index.blade.php ENDPATH**/ ?>