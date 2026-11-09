<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php if (! empty(trim($__env->yieldContent('title')))): ?><?php echo $__env->yieldContent('title'); ?> &mdash; <?php endif; ?> VetBook Admin</title>

    <!-- Google Fonts: Literata (600, 700) + Nunito Sans (400, 500, 600, 700) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,600;0,7..72,700;1,7..72,600&family=Nunito+Sans:ital,opsz,wght@0,6..12,400;0,6..12,500;0,6..12,600;0,6..12,700;1,6..12,400&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --primary-green: #4A7C59;
            --secondary-green: #78A886;
            --pale-green: #C8E8D0;
            --bg-cream: #FAF6F0;
            --card-cream: #F5F1EA;
            --card-beige: #F0ECE4;
            --dark-text: #2E3230;
            --gray-text: #6B6358;
            --border-divider: #EAE6DE;
            --radius-card: 24px;
            --radius-sm: 16px;
            --radius-pill: 9999px;
            --sidebar-width: 240px;
            --topbar-height: 64px;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-cream);
            color: var(--dark-text);
            font-family: 'Nunito Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, .brand-title, .page-header-title {
            font-family: 'Literata', serif;
            color: var(--dark-text);
            letter-spacing: -0.015em;
        }

        /* Fixed Left Sidebar (240px wide, forest green #4A7C59) */
        .admin-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--primary-green);
            z-index: 1030;
            overflow-y: auto;
            color: #FFFFFF;
            transition: transform 0.25s ease-in-out;
            box-shadow: 2px 0 16px rgba(46, 50, 48, 0.08);
        }

        .sidebar-header {
            height: var(--topbar-height);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-header .brand-title {
            font-family: 'Literata', serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: -0.02em;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background-color: rgba(255, 255, 255, 0.18);
            color: #FFFFFF;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .brand-badge {
            background-color: var(--pale-green);
            color: var(--primary-green);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 3px 6px;
            border-radius: 6px;
        }

        /* Sidebar User Info */
        .sidebar-user {
            background: rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .user-avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--pale-green);
            color: var(--primary-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .sidebar-user .user-name {
            color: #FFFFFF;
            font-weight: 600;
            font-size: 0.88rem;
            line-height: 1.25;
        }

        .user-branch-badge {
            background-color: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
            font-size: 0.72rem;
            font-weight: 500;
            border-radius: var(--radius-pill);
            padding: 2px 8px;
            display: inline-block;
            margin-top: 3px;
        }

        /* Sidebar Nav Links */
        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 0.92rem;
            padding: 10px 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background-color 0.15s ease, color 0.15s ease;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .sidebar-nav .nav-link:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .sidebar-nav .nav-link.active {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid #FFFFFF;
            border-top-left-radius: 2px;
            border-bottom-left-radius: 2px;
            font-weight: 600;
        }

        .sidebar-nav .nav-icon {
            font-size: 0.95rem;
            width: 20px;
            text-align: center;
            opacity: 0.95;
        }

        /* Logout button */
        .logout-btn {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #FFFFFF;
            border-color: rgba(255, 255, 255, 0.35);
        }

        /* Main Content Layout (margin-left 240px) */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-cream);
        }

        /* Top Bar (height 64px, bg #FAF6F0, border-bottom, sticky) */
        .admin-topbar {
            height: var(--topbar-height);
            background-color: var(--bg-cream);
            border-bottom: 1px solid var(--border-divider);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
            backdrop-filter: blur(8px);
        }

        .topbar-title {
            font-family: 'Literata', serif;
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark-text);
        }

        .branch-badge {
            background-color: var(--card-cream);
            border: 1px solid var(--border-divider);
            color: var(--primary-green);
            font-weight: 600;
            font-size: 0.82rem;
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .admin-avatar-btn {
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar-btn:focus {
            outline: none;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--primary-green);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            border: 2px solid #FFFFFF;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        /* Dropdown custom styling */
        .custom-dropdown-menu {
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-divider);
            box-shadow: 0 10px 30px rgba(46, 50, 48, 0.08);
            background-color: #FFFFFF;
            min-width: 220px;
            padding: 8px;
        }

        .custom-dropdown-item {
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.88rem;
            color: var(--dark-text);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-dropdown-item:hover {
            background-color: var(--card-cream);
            color: var(--primary-green);
        }

        /* Content Area (padding: 32px) */
        .admin-content {
            padding: 32px;
            flex-grow: 1;
        }

        /* Cards & Alerts default system styling */
        .card {
            background-color: var(--card-cream);
            border: 1px solid var(--border-divider);
            border-radius: var(--radius-card);
            box-shadow: 0 2px 8px rgba(46, 50, 48, 0.03);
        }

        .alert-info {
            background-color: var(--card-cream);
            border: 1px solid var(--border-divider);
            color: var(--dark-text);
            border-radius: var(--radius-sm);
        }

        .alert-custom {
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            padding: 14px 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .alert-custom-success {
            background-color: var(--pale-green);
            border-color: #A9DAB7;
            color: #235431;
        }

        .alert-custom-danger {
            background-color: #FBEBEB;
            border-color: #F5C6CB;
            color: #922B21;
        }

        /* Mobile drawer overlay */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1025;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .sidebar-backdrop.show {
                display: block;
            }
            .admin-main {
                margin-left: 0;
            }
            .admin-topbar {
                padding: 0 16px;
            }
            .admin-content {
                padding: 20px 16px;
            }
        }
    </style>
    <?php echo $__env->yieldContent('styles'); ?>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar Backdrop for Mobile -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Sidebar Partial -->
        <?php echo $__env->make('admin.layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <!-- Main Wrapper -->
        <div class="admin-main flex-grow-1">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm d-lg-none p-0 text-dark" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
                        <i class="fa-solid fa-bars fs-5"></i>
                    </button>
                    <h1 class="topbar-title"><?php echo $__env->yieldContent('title', 'Dashboard'); ?></h1>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Branch Badge -->
                    <div class="branch-badge">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>
                            <?php if(auth()->check() && auth()->user()->branch): ?>
                                <?php echo e(auth()->user()->branch); ?>

                            <?php else: ?>
                                All Branches
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Admin Profile Dropdown -->
                    <div class="dropdown">
                        <button class="admin-avatar-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="admin-avatar">
                                <?php echo e(strtoupper(substr(auth()->user()->name ?? 'A', 0, 1))); ?>

                            </div>
                            <div class="d-none d-md-block text-start">
                                <div class="fw-semibold text-dark" style="font-size: 0.88rem; line-height: 1.2;">
                                    <?php echo e(auth()->user()->name ?? 'Admin User'); ?>

                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <?php echo e(ucfirst(auth()->user()->role ?? 'Admin')); ?>

                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-muted" style="font-size: 0.75rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                            <li class="px-3 py-2 border-bottom border-light">
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo e(auth()->user()->name ?? 'Admin User'); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo e(auth()->user()->email ?? ''); ?></div>
                            </li>
                            <li class="mt-1">
                                <a class="dropdown-item custom-dropdown-item" href="<?php echo e(Route::has('admin.settings.index') ? route('admin.settings.index') : url('/admin/settings')); ?>">
                                    <i class="fa-solid fa-cog text-muted"></i>
                                    <span>Settings</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="<?php echo e(Route::has('admin.logout') ? route('admin.logout') : url('/admin/logout')); ?>" class="m-0">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="dropdown-item custom-dropdown-item text-danger border-0 bg-transparent w-100 text-start">
                                        <i class="fa-solid fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="admin-content">
                <!-- Flash Messages -->
                <?php if(session('success')): ?>
                    <div class="alert alert-custom alert-custom-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                        <div><?php echo e(session('success')); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="alert alert-custom alert-custom-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-5"></i>
                        <div><?php echo e(session('error')); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($errors) && $errors->any()): ?>
                    <div class="alert alert-custom alert-custom-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                        <div>
                            <ul class="mb-0 ps-3">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Content Slot -->
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <!-- Toast Container for Real-Time Admin Alerts -->
    <div id="adminToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1095;"></div>

    <!-- Bootstrap 5 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            function toggleSidebar() {
                if (sidebar) sidebar.classList.toggle('show');
                if (backdrop) backdrop.classList.toggle('show');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            if (backdrop) {
                backdrop.addEventListener('click', toggleSidebar);
            }

            // Real-Time Admin Notification Toasts & Polling
            const toastContainer = document.getElementById('adminToastContainer');
            const seenAlertIds = new Set();
            let isFirstPoll = true;

            function playAdminChime() {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    const ctx = new AudioContext();
                    if (ctx.state === 'suspended') {
                        ctx.resume();
                    }
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.35);
                } catch (e) {}
            }
            window.playAdminChime = playAdminChime;

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            window.dismissAdminAlert = function(id) {
                const toastEl = document.getElementById('toast-alert-' + id);
                if (toastEl) {
                    const bsToast = bootstrap.Toast.getInstance(toastEl);
                    if (bsToast) bsToast.hide();
                    toastEl.remove();
                }
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch('/admin/notifications/' + id + '/mark-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                }).catch(() => {});
            };

            function createToastElement(alert) {
                const isCancel = alert.type && (alert.type.includes('cancel') || alert.type.includes('cancelled'));
                const isResched = alert.type && alert.type.includes('resched');
                const isBooked = alert.type && (alert.type.includes('book') || alert.type === 'appointment_booked');

                let iconClass = 'fa-solid fa-bell';
                let iconBg = '#E8F5E9';
                let iconColor = '#2E7D32';

                if (isCancel) {
                    iconClass = 'fa-solid fa-calendar-xmark';
                    iconBg = '#FFDAD8';
                    iconColor = '#BA1A1A';
                } else if (isResched) {
                    iconClass = 'fa-solid fa-calendar-day';
                    iconBg = '#FFF4E5';
                    iconColor = '#B76E00';
                } else if (isBooked) {
                    iconClass = 'fa-solid fa-calendar-plus';
                    iconBg = '#E8F5E9';
                    iconColor = '#2E7D32';
                }

                const toastDiv = document.createElement('div');
                toastDiv.className = 'toast border-0 shadow-lg mb-3';
                toastDiv.id = 'toast-alert-' + alert.id;
                toastDiv.setAttribute('role', 'alert');
                toastDiv.setAttribute('aria-live', 'assertive');
                toastDiv.setAttribute('aria-atomic', 'true');
                toastDiv.setAttribute('data-bs-autohide', 'false');
                toastDiv.style.borderRadius = '16px';
                toastDiv.style.overflow = 'hidden';
                toastDiv.style.background = '#FFFFFF';
                toastDiv.style.border = '1px solid rgba(0,0,0,0.08)';
                toastDiv.style.minWidth = '320px';
                toastDiv.style.maxWidth = '400px';

                toastDiv.innerHTML = `
                    <div class="toast-header border-0 d-flex align-items-center justify-content-between px-3 pt-3 pb-1" style="background: transparent;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; background: ${iconBg}; color: ${iconColor}; font-size: 13px;">
                                <i class="${iconClass}"></i>
                            </span>
                            <strong class="me-auto text-dark" style="font-family: 'Literata', serif; font-size: 14px;">${escapeHtml(alert.title)}</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted" style="font-size: 11px;">${escapeHtml(alert.time_ago)}</small>
                            <button type="button" class="btn-close" style="font-size: 10px;" data-bs-dismiss="toast" aria-label="Close" onclick="dismissAdminAlert(${alert.id})"></button>
                        </div>
                    </div>
                    <div class="toast-body px-3 pt-1 pb-3" style="font-size: 13px; color: #4A4E4A;">
                        <div class="mb-2" style="line-height: 1.4;">${escapeHtml(alert.message)}</div>
                        <div class="d-flex align-items-center justify-content-between pt-1">
                            ${alert.appointment_id ? `
                                <a href="/admin/appointments/${alert.appointment_id}" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Appointment
                                </a>
                            ` : '<span></span>'}
                            <button type="button" class="btn btn-sm text-secondary" style="font-size: 12px;" onclick="dismissAdminAlert(${alert.id})">
                                Dismiss
                            </button>
                        </div>
                    </div>
                `;

                return toastDiv;
            }

            async function checkAdminAlerts() {
                try {
                    const response = await fetch('/admin/notifications/unread-alerts', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    const alerts = data.alerts || [];

                    if (isFirstPoll) {
                        // On page load, remember existing alerts so we only toast new incoming events
                        alerts.forEach(a => seenAlertIds.add(a.id));
                        isFirstPoll = false;
                        return;
                    }

                    let hasNew = false;
                    for (const alert of alerts) {
                        if (!seenAlertIds.has(alert.id)) {
                            seenAlertIds.add(alert.id);
                            hasNew = true;

                            if (toastContainer) {
                                const toastEl = createToastElement(alert);
                                toastContainer.appendChild(toastEl);
                                const bsToast = new bootstrap.Toast(toastEl, { autohide: false });
                                bsToast.show();
                            }
                        }
                    }

                    if (hasNew) {
                        playAdminChime();
                    }
                } catch (err) {
                    // Silently ignore network hiccups
                }
            }

            // Start polling after page finishes rendering (2.5s delay), and skip if tab is in background
            setTimeout(checkAdminAlerts, 2500);
            setInterval(function () {
                if (!document.hidden) {
                    checkAdminAlerts();
                }
            }, 15000);
        });
    </script>
    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/layouts/app.blade.php ENDPATH**/ ?>