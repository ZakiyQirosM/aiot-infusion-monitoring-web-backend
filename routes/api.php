<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\InfuseeController;

Route::post('/monitoring', [MonitoringController::class, 'store']);
Route::post('/device/ping', [DeviceController::class, 'ping']);
Route::post('/device/off', [DeviceController::class, 'shutdown']);
Route::post('/session-status', [InfuseeController::class, 'checkSessionStatus']);
