<?php

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return redirect(route($request->user()->dashboardRouteName(), absolute: false));
    })->name('dashboard');

    foreach ([
        User::ROLE_ADMIN => 'Admin',
        User::ROLE_GURU => 'Guru',
        User::ROLE_SISWA => 'Siswa',
        User::ROLE_ORANG_TUA => 'Orang Tua',
    ] as $role => $title) {
        Route::prefix($role)->name($role.'.')->middleware('role:'.$role)->group(function () use ($title) {
            Route::get('/dashboard', function () use ($title) {
                return view('dashboard', ['title' => 'Dashboard '.$title]);
            })->name('dashboard');
        });
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
