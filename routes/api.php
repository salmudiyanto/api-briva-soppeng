<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/* SNAP BI BRIVA Routes */
Route::post('/snap/v1.0/access-token/b2b', 'BrivaController@getToken');
Route::post('/snap/v1.0/transfer-va/inquiry', 'BrivaController@inquiry');
Route::post('/snap/v1.0/transfer-va/payment', 'BrivaController@payment');
