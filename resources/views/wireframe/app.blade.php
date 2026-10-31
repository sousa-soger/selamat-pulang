<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10151c">
    <title>{{ $screens[$screen][1] }} | Selamat Pulang OSH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a href="#main-content" class="skip-link">Skip to content</a>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="/" class="brand"><span class="brand-symbol"><x-icon name="shield-check" size="24" /></span><span>selamat pulang<span class="brand-sub">OCCUPATIONAL SAFETY & HEALTH</span></span></a>
        <button class="workplace-switch" data-dialog="workplace"><span class="workplace-symbol">CF</span><span><strong>Contoh Fabrikasi</strong><small>Miri, Sarawak</small></span><x-icon name="chevrons-up-down" size="14" /></button>
        <nav aria-label="Main navigation">
        @foreach(['Overview','Foundation','Management','Assurance'] as $group)
            <p class="nav-group">{{ $group }}</p>
            @foreach($screens as $key => [$category, $title, $icon])
                @if($category === $group)
                <a href="{{ $key === 'dashboard' ? '/' : '/'.$key }}" @if($screen === $key) aria-current="page" @endif class="nav-link"><x-icon :name="$icon" /><span>{{ $title }}</span>@if($key === 'committee')<small class="nav-count">4</small>@endif</a>
                @endif
            @endforeach
        @endforeach
        </nav>
        <div class="sidebar-bottom"><a class="sidebar-help" href="#" data-dialog="help"><x-icon name="circle-help" /><span>Help & guidance</span><x-icon name="arrow-up-right" size="14" /></a><div class="account"><span class="avatar teal">AR</span><span><strong>Aina Rahman</strong><small>OSH coordinator</small></span><button class="icon-button" title="Account" data-dialog="account"><x-icon name="ellipsis" /></button></div></div>
    </aside>
    <div class="workspace">
        <header class="topbar"><div class="breadcrumb"><button class="icon-button menu-button" title="Open navigation" aria-expanded="false" aria-controls="sidebar" data-menu><x-icon name="menu" /></button><span>Workspace</span><x-icon name="chevron-right" size="14" /><strong>{{ $screens[$screen][1] }}</strong></div><div class="topbar-actions"><span class="preview-label"><span></span>Design preview</span><span class="topbar-divider"></span><button class="icon-button notification-button" title="Notifications" data-dialog="notifications"><x-icon name="bell" /><span></span></button><span class="avatar small-avatar">AR</span></div></header>
        <main id="main-content" class="main-content">
            @include('wireframe.pages.'.$screen)
            <footer class="page-footer"><span>Selamat Pulang OSH</span><span>Safe work. Everyone home.</span><span class="mono">WORKSPACE / CF-001</span></footer>
        </main>
    </div>
</div>
<div class="sidebar-overlay" data-menu-close></div>
<dialog id="preview-dialog"><div class="dialog-top"><span class="eyebrow">SELAMAT PULANG OSH</span><button class="icon-button" title="Close dialog" data-close-dialog><x-icon name="x" /></button></div><h2 id="dialog-title"></h2><div id="dialog-body"></div><div class="dialog-bottom"><button class="button" data-close-dialog>Close</button></div></dialog>
<div class="toast" role="status" hidden></div>
</body>
</html>
