<aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <?php
        $sidebarClinicLogo = \App\Models\ClinicSetting::get('clinic_logo') ?: \App\Models\ClinicSetting::get('logo');
        $sidebarLogoUrl = null;
        if (!empty($sidebarClinicLogo)) {
            $sidebarLogoUrl = str_starts_with($sidebarClinicLogo, 'http') ? $sidebarClinicLogo : asset('storage/' . ltrim($sidebarClinicLogo, '/'));
        }
    ?>
    <!-- Brand / Logo (icon + 'VetBook' wordmark in Literata, white) -->
    <div class="sidebar-header px-4 py-3 d-flex align-items-center">
        <a href="<?php echo e(Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin')); ?>" class="text-decoration-none d-flex align-items-center gap-2">
            <?php if(!empty($sidebarLogoUrl)): ?>
                <span class="brand-icon d-inline-flex align-items-center justify-content-center overflow-hidden rounded-circle bg-white" style="border: 1px solid rgba(255,255,255,0.4); padding: 0;">
                    <img src="<?php echo e($sidebarLogoUrl); ?>" alt="Logo" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                </span>
            <?php else: ?>
                <span class="brand-icon d-inline-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-paw"></i>
                </span>
            <?php endif; ?>
            <span class="brand-title">VetBook</span>
            <span class="badge brand-badge ms-1">ADMIN</span>
        </a>
    </div>

    <!-- Below logo: current user info (name + branch badge) -->
    <div class="sidebar-user px-4 py-3 mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="user-avatar-circle">
                <?php echo e(strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1))); ?>

            </div>
            <div class="user-info text-truncate">
                <div class="user-name text-truncate"><?php echo e(auth()->user()?->name ?? 'Admin User'); ?></div>
                <span class="badge user-branch-badge text-truncate" style="max-width: 150px;">
                    <i class="fa-solid fa-code-branch me-1"></i>
                    <?php echo e(auth()->user()?->branch ?? 'All Branches'); ?>

                </span>
            </div>
        </div>
    </div>

    <!-- Navigation items -->
    <nav class="sidebar-nav flex-grow-1 px-2 py-2">
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link <?php echo e((request()->routeIs('admin.dashboard') || request()->routeIs('admin.dashboard.*')) ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin')); ?>">
                    <i class="fa-solid fa-chart-line nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.appointments.*') || request()->routeIs('admin.appointments.index') ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.appointments.index') ? route('admin.appointments.index') : url('/admin/appointments')); ?>">
                    <i class="fa-solid fa-calendar-check nav-icon"></i>
                    <span class="nav-label">Appointments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.pets.*') || request()->routeIs('admin.pets.index') ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.pets.index') ? route('admin.pets.index') : url('/admin/pets')); ?>">
                    <i class="fa-solid fa-paw nav-icon"></i>
                    <span class="nav-label">Pets</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.medical-records.*') || request()->routeIs('admin.medical-records.index') ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.medical-records.index') ? route('admin.medical-records.index') : url('/admin/medical-records')); ?>">
                    <i class="fa-solid fa-file-medical nav-icon"></i>
                    <span class="nav-label">Medical Records</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.pet-owners.*') || request()->routeIs('admin.pet-owners.index') ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.pet-owners.index') ? route('admin.pet-owners.index') : url('/admin/pet-owners')); ?>">
                    <i class="fa-solid fa-users nav-icon"></i>
                    <span class="nav-label">Pet Owners</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.sms.*') || request()->routeIs('admin.sms.index') ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.sms.index') ? route('admin.sms.index') : url('/admin/sms')); ?>">
                    <i class="fa-solid fa-sms nav-icon"></i>
                    <span class="nav-label">SMS Notifications</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('admin.settings.*') || request()->routeIs('admin.settings.index') ? 'active' : ''); ?>" 
                   href="<?php echo e(Route::has('admin.settings.index') ? route('admin.settings.index') : url('/admin/settings')); ?>">
                    <i class="fa-solid fa-cog nav-icon"></i>
                    <span class="nav-label">Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Bottom: Logout button (fa-sign-out-alt) -->
    <div class="sidebar-footer p-3 mt-auto">
        <form method="POST" action="<?php echo e(Route::has('admin.logout') ? route('admin.logout') : url('/admin/logout')); ?>" class="m-0">
            <?php echo csrf_field(); ?>
            <button type="submit" class="logout-btn w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
<?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/layouts/sidebar.blade.php ENDPATH**/ ?>