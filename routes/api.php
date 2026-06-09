<?php

use App\Http\Controllers\Api\TripTicketApprovalController;
use App\Http\Controllers\Api\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileAuthController::class, 'login'])->name('api.login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [MobileAuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('api.logout');
});

Route::middleware(['auth:sanctum'])->prefix('trip-tickets')->name('api.trip-tickets.')->controller(TripTicketApprovalController::class)->group(function () {
    Route::get('/for-approval', 'forApproval')->name('for-approval');
    Route::get('/{tripTicket}', 'show')->name('show');
    Route::post('/{tripTicket}/approve', 'approve')->name('approve');
    Route::post('/{tripTicket}/reject', 'reject')->name('reject');
    Route::post('/{tripTicket}/return', 'returnForCorrection')->name('return');
});
