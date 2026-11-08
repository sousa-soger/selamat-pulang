<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\HraDocumentController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TrainingMaterialController;
use App\Http\Controllers\WorkerPortalController;
use App\Http\Controllers\WorkspaceController;
use App\Models\Organization;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'accounts.auth', ['mode' => 'login'])->name('login');
    Route::view('/register', 'accounts.auth', ['mode' => 'register'])->name('register');
    Route::post('/login', [AccountController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/register', [AccountController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/auth/google', [AccountController::class, 'google'])->name('google');
    Route::get('/auth/google/callback', [AccountController::class, 'callback'])->name('google.callback');
    Route::view('/forgot-password', 'accounts.auth', ['mode' => 'forgot'])->name('password.request');
    Route::post('/forgot-password', [AccountController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('accounts.auth', ['mode' => 'reset', 'token' => $token]))->name('password.reset');
    Route::post('/reset-password', [AccountController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});
Route::post('/stripe/webhook', [BillingController::class, 'webhook'])->name('stripe.webhook');
Route::middleware('auth')->group(function () {
 Route::post('/organizations/{organization}/safety-attachments',[\App\Http\Controllers\SafetyAttachmentController::class,'store'])->middleware('throttle:20,1')->name('safety-attachments.store');
 Route::get('/organizations/{organization}/safety-attachments/{document}',[\App\Http\Controllers\SafetyAttachmentController::class,'download'])->name('safety-attachments.download');
    Route::post('/organizations/{organization}/materials', [TrainingMaterialController::class, 'store'])->middleware('throttle:20,1')->name('materials.store');
    Route::post('/organizations/{organization}/materials/{material}/complete', [TrainingMaterialController::class, 'complete'])->middleware('throttle:30,1')->name('materials.complete');
    Route::get('/organizations/{organization}/materials/{material}/{document}', [TrainingMaterialController::class, 'download'])->name('materials.download');
    Route::post('/organizations/{organization}/governance', [GovernanceController::class, 'store'])->middleware('throttle:20,1')->name('governance.store');
    Route::get('/organizations/{organization}/governance/{document}', [GovernanceController::class, 'download'])->name('governance.download');
    Route::post('/organizations/{organization}/portal', [WorkerPortalController::class, 'store'])->middleware('throttle:30,1')->name('portal.store');
    Route::post('/organizations/{organization}/hra/documents', [HraDocumentController::class, 'store'])->middleware('throttle:10,1')->name('hra.documents.store');
    Route::get('/organizations/{organization}/hra/documents/{document}', [HraDocumentController::class, 'download'])->name('hra.documents.download');
    Route::get('/organizations/{organization}/workspace', [WorkspaceController::class, 'show'])->name('workspace.show');
    Route::patch('/organizations/{organization}/members/{user}/role', [OrganizationController::class, 'updateMemberRole'])->name('organizations.members.role');
    Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::patch('/admin/users/{user}/organization-permission', [AdminController::class, 'setOrganizationPermission'])->name('admin.users.organization-permission');
    Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::post('/admin/users/{user}/verify', [AdminController::class, 'verifyUser'])->name('admin.users.verify');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/account/avatar', [AccountController::class, 'avatar'])->name('profile.avatar');
    Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::patch('/admin/organizations/{organization}', [AdminController::class, 'updateOrganization'])->name('admin.organizations.update');
    Route::get('/organizations/{organization}/suggestions', [OrganizationController::class, 'suggestions'])->middleware('throttle:30,1')->name('members.suggestions');
    Route::post('/organizations/{organization}/select', [WorkspaceController::class, 'select'])->name('organizations.select');
    Route::put('/organizations/{organization}/workspace', [WorkspaceController::class, 'save'])->name('workspace.save');
    Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::get('/email/verify', fn () => redirect()->route('account'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AccountController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AccountController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');
    Route::get('/certificates/{user}', [AccountController::class, 'download'])->name('certificates.download');
    Route::middleware('verified')->group(function () {
        Route::post('/account/certificate', [AccountController::class, 'certificate'])->middleware('throttle:10,1')->name('certificates.store');
        Route::post('/organizations', [OrganizationController::class, 'store'])->middleware('throttle:10,1')->name('organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
        Route::post('/organizations/{organization}/members', [OrganizationController::class, 'addMember'])->name('organizations.members.store');
        Route::delete('/organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember'])->name('organizations.members.destroy');
        Route::post('/organizations/{organization}/checkout', [BillingController::class, 'checkout'])->middleware('throttle:6,1')->name('organizations.checkout');
        Route::post('/organizations/{organization}/billing', [BillingController::class, 'portal'])->name('organizations.billing');
        Route::get('/admin', [AdminController::class, 'index'])->name('admin');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::post('/admin/organizations', [AdminController::class, 'storeOrganization'])->name('admin.organizations.store');
        Route::post('/admin/certificates/{user}', [AdminController::class, 'review'])->name('admin.certificates.review');
    });
});

Route::get('/{screen?}', function (string $screen = 'dashboard') {
    $aliases = ['committee' => 'organization', 'emergency' => 'organization', 'capa' => 'organization', 'personal' => 'learning', 'mentor' => 'learning', 'calendar' => 'organization', 'workplace' => 'organization', 'profile' => 'organization', 'health' => 'hra', 'languages' => 'workerportal', 'oshwa' => 'solve'];
    if (isset($aliases[$screen])) {
        return redirect('/'.$aliases[$screen]);
    }
    $screens = config('adapt360');

    abort_unless(isset($screens[$screen]), 404);

    $organization = Organization::find(session('organization_id'));
    if (! $organization) {
        $organization = auth()->user()->organizations()->first();
        if ($organization) {
            session()->put('organization_id', $organization->id);
        }
    }
    if ($organization && ! $organization->canManage(auth()->user()) && ! $organization->users()->whereKey(auth()->id())->exists()) {
        session()->forget('organization_id');
        $organization = null;
    }

    return view('adapt360.app', compact('screen', 'screens', 'organization'));
})->middleware('auth')->name('wireframe');
