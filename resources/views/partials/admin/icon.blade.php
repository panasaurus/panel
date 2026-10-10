@php
    $paths = [
        'overview' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'servers' => 'M4 3h16v7H4z M4 14h16v7H4z M7 6.5h.01 M7 17.5h.01 M11 6.5h6 M11 17.5h6',
        'nodes' => 'M3 4h18v12H3z M8 21h8 M12 16v5 M7 8h4 M7 12h9',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M9 3a4 4 0 1 0 0 8a4 4 0 0 0 0-8',
        'globe' => 'M21 12a9 9 0 1 0-18 0a9 9 0 0 0 18 0 M3 12h18 M12 3a19 19 0 0 1 0 18a19 19 0 0 1 0-18',
        'database' => 'M20 6c0 2-3.6 3-8 3S4 8 4 6s3.6-3 8-3s8 1 8 3 M4 6v12c0 2 3.6 3 8 3s8-1 8-3V6 M4 12c0 2 3.6 3 8 3s8-1 8-3',
        'settings' => 'M9 3h6l1 3l3 1l2 5l-2 5l-3 1l-1 3H9l-1-3l-3-1l-2-5l2-5l3-1z M16 12a4 4 0 1 0-8 0a4 4 0 0 0 8 0',
        'key' => 'M14 7a4 4 0 1 0-8 0a4 4 0 0 0 8 0 M10 11v10 M10 17h4 M10 21h4',
        'mounts' => 'M3 7h7l2 2h9v11H3z M3 7V4h7l2 3 M8 14h8 M12 10v8',
        'nests' => 'M12 3C8 3 4 11 4 15a8 8 0 0 0 16 0c0-4-4-12-8-12 M8 15a4 4 0 0 0 4 4',
        'extensions' => 'M3 3h6v3a3 3 0 1 0 6 0V3h6v6h-3a3 3 0 1 0 0 6h3v6h-6v-3a3 3 0 1 0-6 0v3H3v-6h3a3 3 0 1 0 0-6H3z',
        'marketplace' => 'M3 10l2-7h14l2 7 M3 10a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0 M4 13v8h16v-8 M9 21v-6h6v6',
        'search' => 'M17 10a7 7 0 1 0-14 0a7 7 0 0 0 14 0 M15 15l6 6',
        'menu' => 'M4 6h16 M4 12h16 M4 18h16',
        'close' => 'M6 6l12 12 M6 18L18 6',
        'arrow' => 'M5 12h14 M13 6l6 6l-6 6',
        'external' => 'M14 3h7v7 M21 3l-9 9 M10 3H3v18h18v-7',
        'plus' => 'M12 5v14 M5 12h14',
        'check' => 'M5 12l4 4L19 6',
        'activity' => 'M3 12h4l3-8l4 16l3-8h4',
        'refresh' => 'M20 7v5h-5 M4 17v-5h5 M6 6a8 8 0 0 1 13 1l1 5 M4 12l1 5a8 8 0 0 0 13 1',
        'logout' => 'M9 3H3v18h6 M9 12h12 M16 7l5 5l-5 5',
        'shield' => 'M12 3l8 4v6c0 4-8 8-8 8s-8-4-8-8V7z M8 12l3 3l5-6',
    ];
@endphp
<svg class="admin-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $paths[$name] ?? $paths['overview'] }}" /></svg>
