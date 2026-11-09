
<?php
    $size = $size ?? 44;
    $avatarPalette = [
        'dog'    => ['#C8E8D0', '#2A6038'],
        'cat'    => ['#F8E0A8', '#705C30'],
        'bird'   => ['#D8E6F0', '#35607E'],
        'rabbit' => ['#FFDAD8', '#B83230'],
    ];
    [$avBg, $avFg] = $avatarPalette[strtolower((string) ($pet->species ?? ''))] ?? ['#EAE6DE', '#4A4E4A'];
    $avatarSrc = ($pet->avatar_url ?? null)
        ? (\Illuminate\Support\Str::startsWith($pet->avatar_url, ['http://', 'https://', '//'])
            ? $pet->avatar_url
            : asset('storage/' . ltrim($pet->avatar_url, '/')))
        : null;
?>
<span class="pet-avatar" style="width: <?php echo e($size); ?>px; height: <?php echo e($size); ?>px; font-size: <?php echo e(round($size * 0.4)); ?>px; background: <?php echo e($avBg); ?>; color: <?php echo e($avFg); ?>;">
    <?php echo e(strtoupper(mb_substr($pet->name ?? '?', 0, 1))); ?>

    <?php if($avatarSrc): ?>
        <img src="<?php echo e($avatarSrc); ?>" alt="<?php echo e($pet->name); ?>" loading="lazy" onerror="this.remove()">
    <?php endif; ?>
</span>
<?php /**PATH C:\Users\Moshi\vetbook-project\backend\resources\views/admin/medical-records/_avatar.blade.php ENDPATH**/ ?>