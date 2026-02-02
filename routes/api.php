<?php

use App\Http\Controllers\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API Works!']);
});

Route::group(['middleware' => 'auth:api'], function () {
    // Test Rate Limiting
    Route::get('test', function () {
        return response()->json(['message' => 'API Works!']);
    });
});
