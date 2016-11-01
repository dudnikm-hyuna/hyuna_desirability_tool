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

Route::get('/home', 'HomeController@index');
Route::get('/tool', 'HomeController@tool');
Route::get('/undesirable-affiliates-data', 'HomeController@getUndesirableAffiliatesData');
Route::get('/undesirable-affiliates-history-data/{id}', 'HomeController@getUndesirableAffiliateHistoryData');
Route::get('/update-undesirable-affiliate/{id}/{wp_id}', 'HomeController@updateUndesirableAffiliateById');
