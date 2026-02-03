<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Storage;
use Mews\Captcha\Captcha;
use Illuminate\Session\Middleware\StartSession;

// Login & Registration URL
Route::post('/users/register', [\App\Http\Controllers\UsersController::class, 'register'])->name('users.register');
Route::post('/users/confirm', [\App\Http\Controllers\UsersController::class, 'confirm'])->name('users.confirm');
Route::post('/users/login', [\App\Http\Controllers\UsersController::class, 'login'])->name('users.login');
Route::post('/users/forgot', [\App\Http\Controllers\UsersController::class, 'forgot'])->name('users.forgot');
Route::post('/users/resetConfirm', [\App\Http\Controllers\UsersController::class, 'resetConfirm'])->name('users.resetConfirm');
Route::put('/users/reset/{id}', [\App\Http\Controllers\UsersController::class, 'reset'])->name('users.reset');

// Custom URL
Route::get('/menu/all', [\App\Http\Controllers\MenuController::class, 'all'])->name('menu.all');
Route::get('/menu/details/{id}', [\App\Http\Controllers\MenuController::class, 'details'])->middleware('auth:api')->name('menu.details');

Route::get('/invoice/details/{id}', [\App\Http\Controllers\InvoiceController::class, 'details'])->middleware('auth:api')->name('invoice.details');
Route::get('/invoice/getImages/{id}', [\App\Http\Controllers\InvoiceController::class, 'getImages'])->middleware('auth:api')->name('invoice.getImages');
Route::put('/invoice/progress/{id}', [\App\Http\Controllers\InvoiceController::class, 'progress'])->middleware('auth:api')->name('invoice.progress');
Route::put('/invoice/approve/{id}', [\App\Http\Controllers\InvoiceController::class, 'approve'])->name('invoice.approve');
Route::put('/invoice/reject/{id}', [\App\Http\Controllers\InvoiceController::class, 'reject'])->name('invoice.reject');

Route::get('/claim/details/{id}', [\App\Http\Controllers\ClaimController::class, 'details'])->middleware('auth:api')->name('claim.details');
Route::get('/claim/serialnumber/{id}', [\App\Http\Controllers\ClaimController::class, 'serialnumber'])->middleware('auth:api')->name('claim.serialnumber');
Route::put('/claim/progress/{id}', [\App\Http\Controllers\ClaimController::class, 'progress'])->middleware('auth:api')->name('claim.progress');
Route::put('/claim/approve/{id}', [\App\Http\Controllers\ClaimController::class, 'approve'])->name('claim.approve');
Route::put('/claim/reject/{id}', [\App\Http\Controllers\ClaimController::class, 'reject'])->name('claim.reject');

Route::get('/role/allDetails', [\App\Http\Controllers\RoleController::class, 'allDetails'])->middleware('auth:api')->name('role.allDetails');
Route::get('/retailer/shop/{id}', [\App\Http\Controllers\RetailerController::class, 'shop'])->name('retailer.shop');

Route::post('/invoice/search', [\App\Http\Controllers\InvoiceController::class, 'search'])->middleware('auth:api')->name('invoice.search');
Route::post('/claim/search', [\App\Http\Controllers\ClaimController::class, 'search'])->middleware('auth:api')->name('claim.search');
Route::post('/item/search', [\App\Http\Controllers\ItemController::class, 'search'])->middleware('auth:api')->name('item.search');
Route::post('/menu/search', [\App\Http\Controllers\MenuController::class, 'search'])->middleware('auth:api')->name('menu.search');
Route::post('/retailer/search', [\App\Http\Controllers\RetailerController::class, 'search'])->middleware('auth:api')->name('retailer.search');
Route::post('/role/search', [\App\Http\Controllers\RoleController::class, 'search'])->middleware('auth:api')->name('role.search');
Route::post('/users/search', [\App\Http\Controllers\UsersController::class, 'search'])->middleware('auth:api')->name('users.search');

Route::get('/invoice/status/{id}', [\App\Http\Controllers\InvoiceController::class, 'status'])->name('invoice.status');
Route::get('/claim/status/{id}', [\App\Http\Controllers\ClaimController::class, 'status'])->name('claim.status');
Route::get('/item/copy', [\App\Http\Controllers\ItemController::class, 'copy'])->name('item.copy');

// jimmi tambah 29 nov'24
Route::post('/brand/search', [\App\Http\Controllers\BrandController::class, 'search'])->middleware('auth:api')->name('brand.search');

Route::post('/brand/unconfirm-user', [\App\Http\Controllers\BrandController::class, 'unconfirmuser'])->name('brand.unconfirmuser');

Route::post('/kombo/search', [\App\Http\Controllers\KomboController::class, 'search'])->middleware('auth:api')->name('kombo.search');

Route::post('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth:api')->name('dashboard.index');
// End Custom Url

// Resources
Route::apiResource('/menu', \App\Http\Controllers\MenuController::class)->middleware('auth:api');
Route::apiResource('/role', \App\Http\Controllers\RoleController::class)->middleware('auth:api');
Route::apiResource('/users', \App\Http\Controllers\UsersController::class)->middleware('auth:api');
Route::apiResource('/retailer', \App\Http\Controllers\RetailerController::class)->middleware('auth:api');
Route::apiResource('/item', \App\Http\Controllers\ItemController::class)->middleware('auth:api');
Route::apiResource('/invoice', \App\Http\Controllers\InvoiceController::class)->middleware('auth:api');
Route::apiResource('/claim', \App\Http\Controllers\ClaimController::class)->middleware('auth:api');

// jimmi tambah 29 nov'24
Route::apiResource('/brand', \App\Http\Controllers\BrandController::class)->middleware('auth:api');
Route::apiResource('/kombo', \App\Http\Controllers\KomboController::class)->middleware('auth:api');

// End Resources

// Captcha
// Route::get('/captcha/{config?}', function($config = 'default') {
//     return captcha($config);
// })->middleware([StartSession::class]);
// Route::post('/verify-captcha', function (\Illuminate\Http\Request $request) {
//     return response()->json([
//         'success' => captcha_check($request->captcha)
//     ]);
// })->middleware(['api', StartSession::class]);

// Artisan
Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
});
// End Artisan
