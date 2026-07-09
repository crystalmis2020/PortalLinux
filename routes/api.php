<?php

use App\Http\Controllers\Api\TripTicketApprovalController;
use App\Http\Controllers\Api\TripTicketGatekeeperController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\TripTicketLocationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [MobileAuthController::class, 'login'])->name('api.login');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [MobileAuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('api.logout');
});


Route::middleware(['auth:sanctum'])->prefix('trip-tickets/locations')->name('api.trip-tickets.locations.')->controller(TripTicketLocationController::class)->group(function () {
    Route::get('/regions', 'regions')->name('regions');
    Route::get('/provinces', 'provinces')->name('provinces');
    Route::get('/cities', 'cities')->name('cities');
    Route::get('/{tripTicketLocation}/distance', 'distance')->name('distance');
});


Route::middleware(['auth:sanctum'])->prefix('trip-tickets/gatekeeper')->name('api.trip-tickets.gatekeeper.')->controller(TripTicketGatekeeperController::class)->group(function () {
    Route::get('/ready-for-departure', 'readyForDeparture')->name('ready-for-departure');
    Route::get('/awaiting-return', 'awaitingReturn')->name('awaiting-return');
    Route::get('/search', 'search')->name('search');
    Route::get('/qr/{token}', 'qrLookup')->where('token', '.*')->name('qr');
    Route::post('/{tripTicket}/departure', 'recordDeparture')->name('departure');
    Route::post('/{tripTicket}/return', 'recordReturn')->name('return');
});

Route::middleware(['auth:sanctum'])->prefix('trip-tickets')->name('api.trip-tickets.')->controller(TripTicketApprovalController::class)->group(function () {
    Route::get('/for-approval', 'forApproval')->name('for-approval');
    Route::get('/{tripTicket}', 'show')->name('show');
    Route::post('/{tripTicket}/approve', 'approve')->name('approve');
    Route::post('/{tripTicket}/reject', 'reject')->name('reject');
    Route::post('/{tripTicket}/return', 'returnForCorrection')->name('return');
});
