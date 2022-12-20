<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Frontend\CallController;
use App\Http\Controllers\Frontend\GuestserviceController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Frontend routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// prefix => frontend, middleware used => ['api', 'api_auth_group']

Route::any('/call/agentstatus', [CallController::class, 'getAgentStatus']);
Route::any('/chat/unreadcount', [DataController::class, 'getChatUnreadCount']);
Route::any('getfavouritemenu', [FrontendController::class, 'getFavouriteMenus']);