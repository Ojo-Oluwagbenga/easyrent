<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Had to configure the controller namespace at app/Providers/RouteServiceProvider.php

Route::get('api/test', 'ApiController@test');
Route::get('', 'ApiController@welcome');

Route::post('/api/{class_name}/{func_name}', 'ApiController@manager');
