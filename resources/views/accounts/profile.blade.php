@extends('accounts.layout')
@section('title', 'My profile')
@section('content')
<div class="page-heading"><span class="eyebrow">MY PROFILE · FREE ACCOUNT</span><h1>{{ $user->name }}</h1><p class="muted">{{ $user->email }} @if($user->isSuperAdmin()) · Super admin @endif</p></div>
@if(!$user->hasVerifiedEmail())<div class="notice"><strong>Verify your email to continue</strong><p>Check your inbox before submitting credentials or creating an organization.</p><form method="post" action="{{ route('verification.send') }}">@csrf<button class="account-button">Resend verification email</button></form></div>@endif
<section class="panel"><h2>Profile information</h2><form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PATCH')<label>Name<input name="name" value="{{ $user->name }}" required></label><label>Profile picture<input name="avatar" type="file" accept="image/png,image/jpeg,image/webp"></label><button class="account-button">Save profile</button></form></section><br><div class="account-grid"><section class="panel"><div class="section-heading"><h2>MyKKP verification</h2><span class="status">{{ str_replace('_',' ',$user->certificate_status) }}</span></div><p class="muted">Add your MyKKP ID and upload your certificate. A super admin will review it manually.</p>
@if($user->certificate_feedback)<div class="notice"><strong>Reviewer feedback</strong><p>{{ $user->certificate_feedback }}</p></div>@endif
@if($user->certificate_path)<p><a href="{{ route('certificates.download',$user) }}">Download submitted certificate</a></p>@endif
<form method="post" action="{{ route('certificates.store') }}" enctype="multipart/form-data">@csrf
<label for="mykkp_id">MyKKP ID</label><input id="mykkp_id" name="mykkp_id" value="{{ old('mykkp_id',$user->mykkp_id) }}" maxlength="100" required>
<label for="certificate">MyKKP certificate</label><input id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" required><small class="muted">PDF, JPG or PNG. Maximum 10 MB. A new upload starts a new review.</small><button class="account-button primary" @disabled(!$user->hasVerifiedEmail())>Submit for verification</button></form></section>
<section class="panel"><h2 id="organizations">Your organizations</h2><p class="muted">Personal profiles are free. Organization access requires a subscription or an administrator provision.</p>
@forelse($organizations as $organization)<a class="list-row" href="{{ route('organizations.show',$organization) }}"><div><strong>{{ $organization->name }}</strong><small>{{ $organization->owner_id === $user->id ? 'Owner' : 'Member' }}</small></div><span class="status">{{ $organization->hasAccess() ? 'Active' : $organization->subscription_status }}</span></a>@empty<p class="empty-state">You do not belong to an organization yet.</p>@endforelse
<hr><h3>Register an organization</h3><form method="post" action="{{ route('organizations.store') }}">@csrf<label for="organization_name">Organization name</label><input id="organization_name" name="name" maxlength="160" required><p class="muted">You can review the subscription price in Stripe before paying.</p><button class="account-button primary" @disabled(!$user->hasVerifiedEmail())>Create organization</button></form></section></div>
<a class="account-button" href="/personal">Skip for now →</a>
@endsection

