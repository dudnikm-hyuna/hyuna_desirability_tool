<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/index', 'DesirabilityToolController@index');
Route::get('/desirability-tool', 'DesirabilityToolController@tool');
Route::get('/undesirable-affiliates-data', 'DesirabilityToolController@getUndesirableAffiliatesData');
Route::get('/undesirable-affiliates-history-data/{id}', 'DesirabilityToolController@getUndesirableAffiliateHistoryData');
Route::get('/send-email/{id}', 'DesirabilityToolController@sendEmail');
Route::get('/set-program/{id}/{wp_id}/{price_program}', 'DesirabilityToolController@setProgram');
