@php
$navigationScreens = collect(config('adapt360'))->except(['workplace', 'committee', 'calendar', 'mentor', 'emergency', 'capa'])->all();
$primaryNavigation = ['dashboard', 'organization', 'next', 'workerportal', 'learning'];
$navigationScreen = $screen ?? null;
$workspaceNavigation = isset($screen);
$navigationOrganization = isset($organization) ? $organization : auth()->user()->organizations()->whereKey(session('organization_id'))->first();
@endphp
    <aside class="sidebar" id="sidebar">
        <a href="/" class="brand adapt-brand" @if($workspaceNavigation) data-section="dashboard" @endif><img src="/images/adapt360-logo.webp" alt="Adapt360 — Adaptive OSH Management Platform for Malaysian SME"></a>
        <a class="workplace-switch" href="{{ $navigationOrganization ? route('organizations.show', $navigationOrganization) : route('account').'#organizations' }}"><span class="workplace-symbol"><x-icon name="building-2" /></span><span><strong id="sidebarCompany">{{ $navigationOrganization?->name ?? 'Your organizations' }}</strong><small id="sidebarSite">Choose or manage an organization</small></span><x-icon name="chevrons-up-down" size="14" /></a>
        <nav aria-label="Main navigation">
        @foreach($primaryNavigation as $key)
            @php([, $title, $icon] = $navigationScreens[$key])
            <a href="{{ $key === 'dashboard' ? '/' : '/'.$key }}" @if($workspaceNavigation) data-section="{{ $key }}" @endif @if($navigationScreen === $key) aria-current="page" @endif class="nav-link"><x-icon :name="$icon" /><span>{{ $title }}</span></a>
        @endforeach
        @if(auth()->user()->isSuperAdmin())<details class="nav-section special-access" @if(request()->routeIs('admin*')) open @endif><summary class="nav-link"><x-icon name="shield-check" /><span>Special Access</span></summary><a class="nav-link" href="{{ route('admin') }}" @if(request()->routeIs('admin*')) aria-current="page" @endif><x-icon name="code" /><span>Developer &amp; administration</span></a></details>@endif
        @foreach(collect($navigationScreens)->except($primaryNavigation)->pluck(0)->unique() as $group)
            @if(!in_array($group, ['Start here', 'Personal', 'Organization']))
            <details class="nav-section" @if(($navigationScreens[$navigationScreen][0] ?? null) === $group) open @endif><summary class="nav-link">{{ $group }}</summary>
            @else
            <p class="nav-group">{{ $group }}</p>
            @endif
            @foreach(collect($navigationScreens)->except($primaryNavigation) as $key => [$category, $title, $icon])
                @if($category === $group)
                <a href="{{ $key === 'dashboard' ? '/' : '/'.$key }}" @if($workspaceNavigation) data-section="{{ $key }}" @endif @if($navigationScreen === $key) aria-current="page" @endif class="nav-link"><x-icon :name="$icon" /><span>{{ $title }}</span></a>
                @endif
            @endforeach
            @if(!in_array($group, ['Start here', 'Personal', 'Organization']))</details>@endif
        @endforeach
        </nav>
        <div class="sidebar-bottom"><a class="sidebar-help" href="/learning" @if($workspaceNavigation) data-section="learning" @endif><x-icon name="circle-help" /><span>Learning & guidance</span><x-icon name="arrow-up-right" size="14" /></a><a class="account" href="{{ route('account') }}"><span class="avatar teal">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isSuperAdmin() ? 'Super admin' : 'My profile & organizations' }}</small></span></a></div>
    </aside>
