{{-- Deterministic colored initial avatar for an owner. Params: $owner, $size (px) --}}
@php
    $size = $size ?? 52;
    $ownerPalette = [
        ['#C8E8D0', '#2A6038'],
        ['#F8E0A8', '#705C30'],
        ['#E8DCC8', '#6B4F2A'],
        ['#D6E8DA', '#3D6B4A'],
        ['#F0E2D0', '#8A5A2B'],
        ['#DCE6D2', '#4A5E2E'],
    ];
    $seed = mb_strtolower(($owner->email ?? '') . '|' . ($owner->name ?? ''));
    [$ownerBg, $ownerFg] = $ownerPalette[crc32($seed) % count($ownerPalette)];
    $avatarSrc = ($owner->avatar_url ?? null)
        ? (\Illuminate\Support\Str::startsWith($owner->avatar_url, ['http://', 'https://', '//'])
            ? $owner->avatar_url
            : asset('storage/' . ltrim($owner->avatar_url, '/')))
        : null;
@endphp
<span class="owner-avatar" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px; background: {{ $ownerBg }}; color: {{ $ownerFg }}; position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%;">
    {{ strtoupper(mb_substr($owner->name ?? '?', 0, 1)) }}
    @if($avatarSrc)
        <img src="{{ $avatarSrc }}" alt="{{ $owner->name }}" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" loading="lazy" onerror="this.remove()">
    @endif
</span>
