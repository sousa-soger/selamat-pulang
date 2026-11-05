<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><link rel="icon" type="image/x-icon" href="{{ asset('adapt360logo-transparent.ico') }}"><meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ $screens[$screen][1] }} | Adapt360</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/adapt360.js'])
</head>
<body data-screen="{{ $screen }}" data-user-id="{{ auth()->id() }}" data-organization-id="{{ $organization?->id }}" data-organization-name="{{ $organization?->name }}" data-workspace-version="{{ $organization?->workspace_version ?? 0 }}">
<script type="application/json" id="workspace-data">{!! json_encode(json_decode($organization?->workspace_data ?? '{}'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script><a href="#main-content" class="skip-link">Skip to content</a>
<div class="app-shell">
    @include('shared.sidebar')
    <div class="workspace">
        <header class="topbar"><div class="breadcrumb"><button class="icon-button menu-button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" data-menu><x-icon name="menu" /></button><a href="/" data-section="dashboard">Home</a><span id="workspaceTrail"></span><x-icon name="chevron-right" size="14" /><strong id="pageTitle">{{ $screens[$screen][1] }}</strong></div><div class="topbar-actions"><span class="preview-label"><span></span>Browser preview</span><select id="uiLang" aria-label="Interface language"><option value="en">EN</option><option value="ms">BM</option></select><button id="organizationChange" class="button primary" onclick="openModal('changeModal')" @if(in_array($screen, ['dashboard', 'personal', 'workerportal', 'mentor'])) hidden @endif><x-icon name="plus" size="14" />Report change</button></div></header>
        <main id="main-content" class="main-content adapt-content">@if(!$organization)<div class="callout"><h2>Select an organization</h2><p>Choose or create an organization to save workplace records.</p><a href="{{ route('account') }}">Your organizations →</a></div>@endif
            <p id="companyLine" class="workspace-context" @if(in_array($screen, ['dashboard', 'personal', 'workerportal', 'mentor'])) hidden @endif></p>
            @foreach($screens as $key => $metadata)
                <section class="adapt-screen" data-workspace="{{ in_array($key, ['personal', 'workerportal', 'mentor']) ? 'Personal' : ($key === 'dashboard' ? 'Home' : 'Organization') }}" id="{{ $key }}" aria-label="{{ $metadata[1] }}" @if($key !== $screen) hidden @endif>
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
