<?php

use Illuminate\Support\Facades\Route;

Route::get('/{screen?}', function (string $screen = 'dashboard') {
    $aliases = ['profile' => 'workplace', 'health' => 'hra', 'languages' => 'workerportal', 'oshwa' => 'solve'];
    if (isset($aliases[$screen])) {
        return redirect('/'.$aliases[$screen]);
    }
    $screens = config('adapt360');

    abort_unless(isset($screens[$screen]), 404);

    return view('adapt360.app', compact('screen', 'screens'));
})->name('wireframe');
