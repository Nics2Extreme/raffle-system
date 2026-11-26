<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\WinnerController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', fn () => redirect()->route('draw.index'))->name('home');

    Route::get('participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::post('participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::post('participants/import', [ParticipantController::class, 'import'])->name('participants.import');
    Route::post('participants/prizes/import', [ParticipantController::class, 'importPrizes'])->name('participants.prizes.import');

    Route::get('draw', [DrawController::class, 'index'])->name('draw.index');
    Route::post('draw', [DrawController::class, 'store'])->name('draw.store');

    Route::get('winners', [WinnerController::class, 'index'])->name('winners.index');
});
