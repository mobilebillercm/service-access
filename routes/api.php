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
Route::post('/clients', 'ApiController@registerUserFromIdentitiesAndAccess')->middleware('rabbitmq.client');

Route::post('/client-service-validities', 'ApiController@createOrUpdateClientServiceValidity')->middleware('rabbitmq.client');



///CONTEXT ROUTES
Route::post('/client-service-disable', 'ValidityManagementController@disableClientServiceValidity')->middleware('token.verification');

Route::post('/client-service-enable', 'ValidityManagementController@renableClientServiceValidity')->middleware('token.verification');

Route::post('/client-service-validity-check', 'ValidityManagementController@isClientServiceValidityValid')->middleware('token.verification');


















Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
