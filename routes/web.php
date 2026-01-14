<?php

use App\Http\Controllers\InfuseeController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Session;
use App\Http\Middleware\AuthPractitioner;
use App\Http\Controllers\HistActivityController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\EndpointSettingController;

Route::get('/', function () {
    return view('landingpage.index');
})->name('landing');

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout'); 

Route::get('/reset-password-form', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password-send', [ForgotPasswordController::class, 'sendResetLink'])->name('password.reset.sendlink');

Route::get('/set-new-password', [ForgotPasswordController::class, 'showNewPasswordForm'])->name('password.set.form');
Route::post('/set-new-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.set.submit');

Route::middleware(['auth:pegawai', 'admin.only'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/endpoint-setting', [EndpointSettingController::class, 'index'])
            ->name('admin.endpoint.index');

        Route::post('/endpoint-setting', [EndpointSettingController::class, 'store'])
            ->name('admin.endpoint.store');

        Route::put('/endpoint-setting/{id}', [EndpointSettingController::class, 'update'])
            ->name('admin.endpoint.update');

        Route::delete('/endpoint-setting/{id}', [EndpointSettingController::class, 'destroy'])
            ->name('admin.endpoint.delete');
    });

Route::middleware(['auth:pegawai'])->group(function () {
    Route::get('/register', [RegisterController::class, 'index'])->name('register.index');
    Route::get('/register/search', [RegisterController::class, 'search'])->name('register.search');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('/devices/select', [DeviceController::class, 'select'])->name('devices.select');
    Route::post('/devices/assign', [DeviceController::class, 'assign'])->name('devices.assign');
    Route::get('/devices/status/{deviceId}', [DeviceController::class, 'status']);
    Route::delete('/infusion-session/clear/{id_session}', [DeviceController::class, 'clear'])->name('infusion.clear');

    Route::post('/infusee/end-session/{id_session}', [InfuseeController::class, 'endSession'])->name('infusee.endSession');
    Route::get('/infusee/{infusee}', [InfuseeController::class, 'show']);
    Route::post('/infusee/end-session/{id_session}', [InfuseeController::class, 'endSession'])->name('infusee.endSession');

    Route::get('/log-aktivitas', [HistActivityController::class, 'index'])->name('activity.index');
});

Route::get('/infusee', [InfuseeController::class, 'index'])->name('infusee.index');
Route::get('/get-latest-infus', [InfuseeController::class, 'getLatestInfus']);








