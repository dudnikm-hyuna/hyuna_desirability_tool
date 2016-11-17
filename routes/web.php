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

Route::get('/index', 'DesirabilityToolController@cron');
Route::get('/desirability-tool', 'DesirabilityToolController@index');
Route::get('/undesirable-affiliates-data', 'DesirabilityToolController@getUndesirableAffiliatesData');
Route::get('/undesirable-affiliates-history-data/{id}', 'DesirabilityToolController@getUndesirableAffiliateHistoryData');
Route::get('/send-email/{id}', 'DesirabilityToolController@sendEmail');
Route::get('/set-workout-program/{id}/{wp_id}/{program_price}', 'DesirabilityToolController@setWorkoutProgram');
Route::get('/undesirable-affiliate/{id}', 'DesirabilityToolController@getUndesirableAffiliateHistoryLogData');

Route::get('/administration', 'AdminController@index');
Route::get('/users-data', 'AdminController@getUsersData');
Route::get('/change-user-role/{id}/{is_admin}', 'AdminController@changeUserRole');
