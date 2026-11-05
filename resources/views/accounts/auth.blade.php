@extends('accounts.layout')
@section('title', $mode === 'register' ? 'Create your free profile' : 'Log in')
@section('content')
<div class="auth-layout"><section class="auth-intro"><span class="eyebrow">YOUR SAFETY JOURNEY</span><h1>Good work starts<br><span>with safer people.</span></h1><p>Your profile, credentials and organizations.<br>One place to keep moving forward.</p><div class="intro-detail"><span>01</span><div><strong>A profile that is yours</strong><p>Register for free and submit your MyKKP certificate for verification.</p></div></div><div class="intro-detail"><span>02</span><div><strong>A workspace for your team</strong><p>Create an organization with a subscription and bring your people together.</p></div></div></section>
<section class="auth-form panel">
@if(in_array($mode,['login','register']))
<span class="eyebrow">ADAPT360 ACCOUNT</span><h2>{{ $mode === 'register' ? 'Create your free profile' : 'Welcome back' }}</h2><p class="muted">{{ $mode === 'register' ? 'Start with you. Add an organization when you need one.' : 'Log in to your profile and organization.' }}</p>
<a class="account-button google-button" href="{{ route('google') }}"><span aria-hidden="true">G</span> Continue with Google</a><div class="divider">or use your email</div>
<form method="post" action="{{ route($mode) }}">@csrf
@if($mode === 'register')<label for="name">Full name (as per IC)</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120">@endif
<label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}" required @if($mode === 'register') minlength="12" @endif>
@if($mode === 'register')<small class="muted">Use at least 12 characters.</small><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
@else<div class="form-between"><label class="check"><input name="remember" type="checkbox" value="1"> Remember me</label><a href="{{ route('password.request') }}">Forgot password?</a></div>@endif
<button class="account-button primary wide">{{ $mode === 'register' ? 'Create free profile' : 'Log in' }}</button></form>
<p class="auth-switch">{{ $mode === 'register' ? 'Already have a profile?' : 'New to Adapt360?' }} <a href="{{ route($mode === 'register' ? 'login' : 'register') }}">{{ $mode === 'register' ? 'Log in' : 'Create a free profile' }}</a></p>
@else
<h2>{{ $mode === 'forgot' ? 'Reset your password' : 'Choose a new password' }}</h2><p class="muted">Get back to your Adapt360 profile.</p>
<form method="post" action="{{ route($mode === 'forgot' ? 'password.email' : 'password.update') }}">@csrf
@if($mode === 'reset')<input type="hidden" name="token" value="{{ $token }}">@endif
<label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email', request('email')) }}" required autocomplete="email">
@if($mode === 'reset')<label for="password">New password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>@endif
<button class="account-button primary wide">{{ $mode === 'forgot' ? 'Send reset link' : 'Save new password' }}</button></form>
@endif
</section></div>
@endsection

