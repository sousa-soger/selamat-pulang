<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><link rel="icon" type="image/x-icon" href="{{ asset('adapt360logo-transparent.ico') }}"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'Your account') | Adapt360</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/accounts.js'])
<link rel="stylesheet" href="{{ asset('css/accounts.css') }}">
</head><body class="accounts @auth account-workspace @endauth">
@auth<div class="app-shell">@include('shared.sidebar')<div class="workspace"><header class="topbar"><div class="breadcrumb"><button class="icon-button menu-button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false" data-menu><x-icon name="menu" /></button><a href="/">Home</a><x-icon name="chevron-right" size="14" /><strong>@yield('title', 'My profile')</strong></div><form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Log out</button></form></header>@else
<header class="account-header"><a href="{{ url('/') }}" aria-label="Adapt360 home"><img src="{{ asset('images/adapt360-logo.webp') }}" alt="Adapt360"></a>
<nav aria-label="Account navigation">@auth<a href="{{ url('/') }}">Workspace</a><a href="{{ route('account') }}">My profile</a>@if(auth()->user()->isSuperAdmin())<a href="{{ route('admin') }}">Developer</a>@endif<form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Log out</button></form>@else<a href="{{ route('login') }}">Log in</a><a href="{{ route('register') }}">Create free profile</a>@endauth</nav></header>@endauth
<main class="account-main" id="main-content">
@if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Please check the following</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main><footer class="account-footer">Adapt360 · Adapt to change. Keep people safe.</footer>
@auth</div></div><div class="sidebar-overlay" data-menu-close></div>@endauth
</body></html>

