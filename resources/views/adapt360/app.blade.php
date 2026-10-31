<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ $screens[$screen][1] }} | Adapt360</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/adapt360.js'])
</head>
<body data-screen="{{ $screen }}">
<a href="#main-content" class="skip-link">Skip to content</a>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="/" class="brand adapt-brand" data-section="dashboard"><img src="/images/adapt360-logo.webp" alt="Adapt360 — Adaptive OSH Management Platform for Malaysian SME"></a>
        <button class="workplace-switch" onclick="openCompanyEditor()"><span class="workplace-symbol">ME</span><span><strong id="sidebarCompany">Maju Engineering</strong><small id="sidebarSite">Miri, Sarawak</small></span><x-icon name="chevrons-up-down" size="14" /></button>
        <nav aria-label="Main navigation">
        @foreach(collect($screens)->pluck(0)->unique() as $group)
            <p class="nav-group">{{ $group }}</p>
            @foreach($screens as $key => [$category, $title, $icon])
                @if($category === $group)
                <a href="{{ $key === 'dashboard' ? '/' : '/'.$key }}" data-section="{{ $key }}" @if($screen === $key) aria-current="page" @endif class="nav-link"><x-icon :name="$icon" /><span>{{ $title }}</span></a>
                @endif
            @endforeach
        @endforeach
        </nav>
        <div class="sidebar-bottom"><a class="sidebar-help" href="/mentor" data-section="mentor"><x-icon name="circle-help" /><span>Help & guidance</span><x-icon name="arrow-up-right" size="14" /></a><div class="account"><span class="avatar teal">AR</span><span><strong>Aina Rahman</strong><small>OSH coordinator</small></span></div></div>
    </aside>
    <div class="workspace">
        <header class="topbar"><div class="breadcrumb"><button class="icon-button menu-button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" data-menu><x-icon name="menu" /></button><span>Workspace</span><x-icon name="chevron-right" size="14" /><strong id="pageTitle">{{ $screens[$screen][1] }}</strong></div><div class="topbar-actions"><span class="preview-label"><span></span>Browser preview</span><select id="uiLang" aria-label="Interface language"><option value="en">EN</option><option value="ms">BM</option></select><button class="button primary" onclick="openModal('changeModal')"><x-icon name="plus" size="14" />Report change</button></div></header>
        <main id="main-content" class="main-content adapt-content">
            <p id="companyLine" class="workspace-context"></p>
            @foreach($screens as $key => $metadata)
                <section class="adapt-screen" id="{{ $key }}" aria-label="{{ $metadata[1] }}" @if($key !== $screen) hidden @endif>
                    @include('adapt360.pages.'.$key)
                </section>
            @endforeach
            <footer class="page-footer"><span>Adapt360</span><span>Adapt to change. Keep people safe.</span><span class="mono">LOCAL WORKSPACE PREVIEW</span></footer>
        </main>
    </div>
</div>
<div class="sidebar-overlay" data-menu-close></div>
@include('adapt360.dialogs')
<dialog class="adapt-modal" id="assessmentModal" aria-labelledby="assessmentTitle">
    <form class="modal-card" id="assessmentForm">
        <div class="modal-head"><h3 id="assessmentTitle">Health assessment review</h3><button type="button" aria-label="Close dialog" onclick="closeModal('assessmentModal')">×</button></div>
        <p class="note">Record the review and supporting report details. Recommendations can be tracked as corrective actions.</p>
        <div class="form-grid">
            <div><label for="assessmentStatus">Review status</label><select id="assessmentStatus"><option>Not assessed</option><option>Partial</option><option>Compliant</option><option>Not compliant</option></select></div>
            <div><label for="assessmentOwner">Owner / assessor</label><input id="assessmentOwner" required></div>
            <div><label for="assessmentReport">Report reference</label><input id="assessmentReport" placeholder="Reference or screening record"></div>
            <div><label for="assessmentDue">Review due date</label><input id="assessmentDue" type="date" required></div>
        </div>
        <label for="assessmentNotes">Screening findings / recommendation</label><textarea id="assessmentNotes" required></textarea>
        <label><input id="assessmentAction" type="checkbox" checked> Create a linked corrective action</label>
        <div class="toolbar"><button class="btn primary" type="submit">Save assessment review</button></div>
    </form>
</dialog>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
