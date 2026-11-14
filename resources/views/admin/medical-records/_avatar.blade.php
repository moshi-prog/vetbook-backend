{{-- Circular pet avatar. Params: $pet, $size (px, default 44) --}}
@php
    $size = $size ?? 44;
    $avatarPalette = [
        'dog'    => ['#C8E8D0', '#2A6038'],
        'cat'    => ['#F8E0A8', '#705C30'],
        'bird'   => ['#D8E6F0', '#35607E'],
        'rabbit' => ['#FFDAD8', '#B83230'],
    ];
    [$avBg, $avFg] = $avatarPalette[strtolower((string) ($pet->species ?? ''))] ?? ['#EAE6DE', '#4A4E4A'];
    $avatarSrc = ($pet->avatar_url ?? null)
        ? (\Illuminate\Support\Str::startsWith($pet->avatar_url, ['http://', 'https://', '//', 'data:image/'])
            ? $pet->avatar_url
            : asset('storage/' . ltrim($pet->avatar_url, '/')))
        : null;
@endphp
<span class="pet-avatar" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.4) }}px; background: {{ $avBg }}; color: {{ $avFg }};">
    {{ strtoupper(mb_substr($pet->name ?? '?', 0, 1)) }}
    @if($avatarSrc)
        <img src="{{ $avatarSrc }}" alt="{{ $pet->name }}" loading="lazy" onerror="this.remove()">
    @endif
</span>
