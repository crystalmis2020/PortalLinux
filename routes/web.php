<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AdministrativeToolController;
use App\Http\Controllers\LeaveMonitoringController;
use App\Http\Controllers\SlotLocatorController;
use App\Http\Controllers\NetworkHostController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\InternetAccessRequestController;
use App\Http\Controllers\TripTicketController;
use App\Http\Controllers\TripTicketDriverController;
use App\Http\Controllers\TripTicketLocationController;
use App\Http\Controllers\TripTicketVehicleController;


use App\Http\Controllers\ReportsBackTransferController;
Route::get('/reports-back-view', [ReportsBackTransferController::class, 'index'])->name('reports.back.view');
Route::post('/reports-back-transfer', [ReportsBackTransferController::class, 'transfer'])->name('reports.back.transfer');
Route::get('/report-logs-back-view', [ReportsBackTransferController::class, 'index2'])->name('report_logs.back.view');
Route::post('/report-logs-back-transfer', [ReportsBackTransferController::class, 'transfer2'])->name('report_logs.back.transfer');

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Dashboard Route
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // Report Saving via AJAX
    Route::post('/reports/save', [DashboardController::class, 'saveReport'])->name('dashboard.reports.save');
});

Route::middleware(['auth'])->prefix('messenger')->name('messenger.')->controller(MessengerController::class)->group(function () {
    Route::get('/contacts', 'contacts')->name('contacts');
    Route::get('/conversation/{user}', 'conversation')->name('conversation');
    Route::post('/conversation/{user}', 'store')->name('store');
    Route::post('/broadcast', 'storeMany')->name('store-many');
    Route::post('/conversations/{conversation}/report', 'createReport')->name('conversations.report');
    Route::post('/presence', 'updatePresenceVisibility')->name('presence.update');
    Route::post('/call/{user}/signal', 'signalCall')->name('call.signal');
    Route::get('/attachments/{message}/view', 'viewAttachment')->name('attachments.view');
    Route::get('/attachments/{message}/download', 'downloadAttachment')->name('attachments.download');
});

Route::middleware(['auth'])->prefix('internet-access')->name('internet-access.')->controller(InternetAccessRequestController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{tripTicket}/edit', 'edit')->name('edit');
    Route::put('/{tripTicket}', 'update')->name('update');
    Route::get('/status/{internetAccessRequest}', 'status')->name('status');
});

Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->controller(NotificationController::class)->group(function () {
    Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
});


// Administrative Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/administrative', [AdministrativeToolController::class, 'index'])->name('administrative.index');
    Route::post('/administrative/user/save', [AdministrativeToolController::class, 'saveUser'])->name('administrative.user.save');
    Route::get('/administrative/get-sections/{department}', [AdministrativeToolController::class, 'getSections'])->name('administrative.get-sections');
    Route::get('/administrative/user/edit/{user}', [AdministrativeToolController::class, 'edit'])->name('administrative.user.edit');
    Route::post('/administrative/users/{user}/update', [AdministrativeToolController::class, 'updateUser'])->name('administrative.user.update');
    Route::post('/administrative/users/{user}/update-password', [AdministrativeToolController::class, 'updatePassword'])->name('administrative.user.update-password');
    Route::delete('/administrative/users/{user}', [AdministrativeToolController::class, 'destroyUser'])->name('administrative.user.destroy');
});

// Report Routes
Route::middleware(['auth'])->prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/assigned-to/{user?}/status/{status?}', 'index')->name('filter');
    Route::get('/export/assigned-to/{user?}/status/{status?}', 'export')->name('export');
    Route::post('/export-selected/assigned-to/{user?}/status/{status?}', 'exportSelected')->name('export-selected');
    Route::get('/details/{report}/{notification_id?}', 'details')->name('details');
    Route::post('/{report}/update', 'update')->name('update');
    Route::post('/assign/{report}', 'assign')->name('assign');
    Route::post('/assign2/{report}', 'assign2')->name('assign2');
    Route::post('/reassign/{report}', 'reassign')->name('reassign');
    Route::post('/resolve/{report}', 'resolve')->name('resolve');
    Route::post('/reopen/{id}', 'reopen')->name('reopen');
    Route::post('/close/{id}', 'close')->name('close');
    Route::post('/message/{report}', 'message')->name('message');
    Route::post('/{id}/upload', 'uploadAttachment')->name('upload');
    Route::get('/attachments/{attachment}/view', 'viewAttachment')->name('attachments.view');
    Route::get('/download/{attachment}', 'downloadFile')->name('download');
});

// Setting Route
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::post('/settings/update-password', [SettingController::class, 'updatePassword'])->name('settings.update-password');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
});

// Personnel Leave Monitoring Route
Route::middleware(['auth'])->group(function () {
    Route::get('/leave-monitoring', [LeaveMonitoringController::class, 'index'])->name('leave-monitoring.index');
    Route::post('/leave-monitoring', [LeaveMonitoringController::class, 'store'])->name('leave-monitoring.store');

    // (Store/Update/Delete routes to be added later)
});

// Trip Tickets
Route::middleware(['auth'])->prefix('trip-tickets/locations')->name('trip-tickets.locations.')->controller(TripTicketLocationController::class)->group(function () {
    Route::get('/regions', 'regions')->name('regions');
    Route::get('/provinces', 'provinces')->name('provinces');
    Route::get('/cities', 'cities')->name('cities');
    Route::get('/{tripTicketLocation}/distance', 'distance')->name('distance');
});

Route::middleware(['auth'])->prefix('trip-tickets')->name('trip-tickets.')->controller(TripTicketController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/drivers', [TripTicketDriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [TripTicketDriverController::class, 'store'])->name('drivers.store');
    Route::get('/drivers/{driver}/edit', [TripTicketDriverController::class, 'edit'])->name('drivers.edit');
    Route::put('/drivers/{driver}', [TripTicketDriverController::class, 'update'])->name('drivers.update');
    Route::delete('/drivers/{driver}', [TripTicketDriverController::class, 'destroy'])->name('drivers.destroy');
    Route::get('/vehicles', [TripTicketVehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/vehicles', [TripTicketVehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}/edit', [TripTicketVehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [TripTicketVehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [TripTicketVehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{tripTicket}/availability', 'availability')->name('availability');
    Route::get('/{tripTicket}/edit', 'edit')->name('edit');
    Route::put('/{tripTicket}', 'update')->name('update');
    Route::post('/{tripTicket}/encode', 'encode')->name('encode');
    Route::get('/{tripTicket}/print', 'print')->name('print');
    Route::get('/{tripTicket}', 'show')->name('show');
});

// MIS Item Inventory
Route::middleware(['auth'])->prefix('inventory-items')->name('inventory-items.')->controller(InventoryItemController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{tripTicket}/edit', 'edit')->name('edit');
    Route::put('/{tripTicket}', 'update')->name('update');
    Route::put('/{inventoryItem}', 'update')->name('update');
    Route::delete('/{inventoryItem}', 'destroy')->name('destroy');
    Route::post('/{inventoryItem}/release', 'release')->name('release');
    Route::put('/releases/{inventoryItemRelease}', 'updateRelease')->name('releases.update');
    Route::delete('/releases/{inventoryItemRelease}', 'destroyRelease')->name('releases.destroy');
    Route::get('/{inventoryItem}', 'show')->name('show');
    Route::get('/{inventoryItem}/history', 'history')->name('history');
    Route::post('/{inventoryItem}/parts', 'storePart')->name('parts.store');
    Route::put('/{inventoryItem}/parts/{inventoryItemPart}', 'updatePart')->name('parts.update');
    Route::delete('/{inventoryItem}/parts/{inventoryItemPart}', 'destroyPart')->name('parts.destroy');
    Route::post('/{inventoryItem}/parts/{inventoryItemPart}/damage', 'markPartAsDamaged')->name('parts.damage');
    Route::post('/{inventoryItem}/parts/{inventoryItemPart}/replace', 'replacePart')->name('parts.replace');
});


// IP checking
Route::middleware(['auth'])->group(function () {
    Route::resource('network-hosts', NetworkHostController::class)->except(['create','edit','show']);
    Route::post('network-hosts/{networkHost}/check', [NetworkHostController::class, 'check'])->name('network-hosts.check');
    Route::post('network-hosts/check-all', [NetworkHostController::class, 'checkAll'])->name('network-hosts.check-all');

    Route::post('network-hosts/categories', [NetworkHostController::class, 'storeCategory'])->name('network-hosts.categories.store');
    Route::get('network-hosts/categories',  [NetworkHostController::class, 'listCategories'])->name('network-hosts.categories.index');
    Route::put('network-hosts/categories/{hostCategory}',    [NetworkHostController::class, 'updateCategory'])->name('network-hosts.categories.update');
    Route::delete('network-hosts/categories/{hostCategory}', [NetworkHostController::class, 'destroyCategory'])->name('network-hosts.categories.destroy');
});


// Slot Locator
Route::middleware(['auth'])->group(function () {
    Route::get('/slot-locator', [SlotLocatorController::class, 'index'])->name('slot-locator.index');
    Route::get('/slot-locator/items/{coordinates}', [SlotLocatorController::class, 'getItems'])->name('slot-locator.items.get');
    Route::post('/slot-locator/items', [SlotLocatorController::class, 'storeItem'])->name('slot-locator.items.store');
    Route::patch('/slot-locator/items/{slotLocator}', [SlotLocatorController::class, 'updateItem'])->name('slot-locator.items.update');
    Route::delete('/slot-locator/items/{slotLocator}', [SlotLocatorController::class, 'destroyItem'])->name('slot-locator.items.destroy');
});

// Unfinished controller
Route::middleware(['auth'])->group(function () {
    Route::view('/user', 'page-underconstruction')->name('user');
    Route::view('/messhall', 'page-underconstruction')->name('messhall');
    Route::redirect('/trip-ticket', '/trip-tickets')->name('trip-ticket');
});


Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

//require __DIR__.'/auth.php';
