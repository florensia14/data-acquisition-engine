<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebsiteController;
use App\Http\Controllers\Api\DomainController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/extract/website', [WebsiteController::class, 'extract']);

Route::post('/extract/domain', [DomainController::class, 'extract']);