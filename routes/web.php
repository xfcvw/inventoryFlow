<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect()->route('app') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::get('/invite/{token}', [InvitationAcceptController::class, 'show'])->name('invitation.show');
Route::post('/invite/{token}/accept', [InvitationAcceptController::class, 'accept'])->middleware('auth')->name('invitation.accept');

Route::middleware('auth')->group(function () {
    Route::view('/app', 'app')->name('app');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
