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


///API ROUTES
Route::post('/clients', 'ApiController@registerUserFromIdentitiesAndAccess');

Route::post('/client-service-validities', 'ApiController@addClientServiceValidity');



///CONTEXT ROUTES
Route::post('/client-service-disable', 'ValidityManagementController@disableClientServiceValidity');

Route::post('/client-service-enable', 'ValidityManagementController@renableClientServiceValidity');

Route::post('/client-service-validity-check', 'ValidityManagementController@isClientServiceValidityValid');


















Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
