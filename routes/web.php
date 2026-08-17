<?php
use App\Http\Controllers\AuthController; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>Auth::check()?redirect()->route('app'):redirect()->route('login'));
Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'create'])->name('login');Route::post('/login',[AuthController::class,'store'])->middleware('throttle:5,1')->name('login.store');});
Route::middleware('auth')->group(function(){Route::view('/app','app')->name('app');Route::post('/logout',[AuthController::class,'destroy'])->name('logout');});
