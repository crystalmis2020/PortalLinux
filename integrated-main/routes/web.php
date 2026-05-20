<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'LandingController@index')->name('landing');
Route::get('/somewhere', 'DashboardController@index')->name('dashboard');

Route::get('/testAPI', 'TestingAPI@test');

Route::get('/users', 'UsersController@index')->name('usersView');
Route::get('/users/create', 'UsersController@create')->name('userCreate');
Route::post('/users', 'UsersController@store');
Route::get('/users/{id}/{username}', 'UsersController@destroy')->name('userDestroy');

Route::get('/profiles', 'ProfilesController@index')->name('profilesView');
Route::get('/profiles/create', 'ProfilesController@create')->name('profileCreate');

Route::get('/profiles/limitations', 'ProfilesController@limitations')->name('limitationsView');
Route::get('/profiles/limitations/create', 'ProfilesController@limitationCreate')->name('limitationCreate');
Route::post('/profiles/limitations/store', 'ProfilesController@limitationStore')->name('limitationStore');
Route::get('/profiles/limitations/delete/{id}/{name}', 'ProfilesController@limitationDestroy')->name('limitationDestroy');
Route::get('/profiles/limitations/edit/{id}', 'ProfilesController@limitationEdit')->name('limitationEdit');
Route::put('/profiles/limitations/update','ProfilesController@limitationUpdate')->name('limitationUpdate');


Route::get('/rfi' , 'rfi\RequestController@index')->name('requestDash');
Route::post('rfi', 'rfi\RequestController@store');
Route::get('/rfi/forApproval/{id}', 'rfi\RequestController@forApproval')->name('requestForApproval');
Route::get('/rfi/admin', 'rfi\AdminRequestController@index')->name('requestAdminDash');
Route::get('/rfi/admin/history','rfi\AdminRequestController@history')->name('requestAdminHistory');
Route::get('/rfi/admin/update/{action}/{id}', 'rfi\AdminRequestController@update')->name('requestAdminUpdate');
Route::get('/rfi/admin/access','rfi\AdminRequestController@access')->name('requestAdminAccess');
Route::get('/rfi/admin/access/mt','rfi\AdminRequestController@mikrotik')->name('requestAdminAccessMT');
Route::get('/rfi/admin/destroyMT/{username}', 'rfi\AdminRequestController@destroy')->name('requestAdminAccessDestroy');

Route::get('/crons/usedUptime', 'Crons@usedUptime');

Route::get('/rfi/admin/access/IpRoute','rfi\AdminRequestController@IpRoute')->name('IpRoute');