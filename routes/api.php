<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserTenantController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\MenuCategoriesController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuTenantController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NotificationController;


Route::group([
    'middleware' => 'api'
], function ($router) {

    //Auth
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    //User
    Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::get('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');

    //Area
    Route::get('/area', [AreaController::class, 'index'])->middleware('auth:api')->name('area');
    Route::post('/area', [AreaController::class, 'store'])->middleware('auth:api', 'role:ADMIN')->name('addArea');
    Route::get('/area/{area}', [AreaController::class, 'show'])->middleware('auth:api')->name('showArea');
    Route::put('/area/{area}', [AreaController::class, 'update'])->middleware('auth:api', 'role:ADMIN')->name('updateArea');
    Route::delete('/area/{area}', [AreaController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN')->name('deleteArea');

    //Table
    Route::get('/table', [TableController::class, 'index'])->middleware('auth:api')->name('table');
    Route::get('/table-available', [TableController::class, 'tableAvailable'])->middleware('auth:api')->name('tableAvailable');
    Route::post('/table', [TableController::class, 'store'])->middleware('auth:api', 'role:ADMIN')->name('addTable');
    Route::get('/table/{table}', [TableController::class, 'show'])->middleware('auth:api')->name('showTable');
    Route::put('/table', [TableController::class, 'update'])->middleware('auth:api', 'role:ADMIN')->name('updateTable');
    Route::delete('/table/{table}', [TableController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN')->name('deleteTable');

    //Tenant
    Route::get('/tenant', [TenantController::class, 'index'])->middleware('auth:api')->name('tenant');
    Route::post('/tenant', [TenantController::class, 'store'])->middleware('auth:api', 'role:ADMIN')->name('addTenant');
    Route::get('/tenant/{tenant}', [TenantController::class, 'show'])->middleware('auth:api')->name('showTenant');
    Route::put('/tenant/{tenant}', [TenantController::class, 'update'])->middleware('auth:api', 'role:ADMIN')->name('updateTenant');
    Route::delete('/tenant/{tenant}', [TenantController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN')->name('deleteTenant');

    Route::group(['prefix' => 'user'], function () {
        //User Tenant
        Route::get('/tenant', [UserTenantController::class, 'index'])->middleware('auth:api', 'role:ADMIN, TENANT')->name('userTenant');
        Route::post('/tenant', [UserTenantController::class, 'store'])->middleware('auth:api', 'role:ADMIN, TENANT')->name('addUserTenant');
        Route::get('/tenant/{userTenant}', [UserTenantController::class, 'show'])->middleware('auth:api', 'role:ADMIN, TENANT')->name('showUserTenant');
        Route::put('/tenant/{userTenant}', [UserTenantController::class, 'update'])->middleware('auth:api', 'role:ADMIN, TENANT')->name('updateUserTenant');
        Route::delete('/tenant/{userTenant}', [UserTenantController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN, TENANT')->name('deleteUserTenant');

        //User Crew
        Route::get('/crew', [UserController::class, 'getCashiersAndWaiters'])->middleware('auth:api', 'role:ADMIN')->name('userCrew');
        Route::post('/crew', [UserController::class, 'store'])->middleware('auth:api', 'role:ADMIN')->name('addUserCrew');
        Route::put('/crew/{user}', [UserController::class, 'update'])->middleware('auth:api', 'role:ADMIN')->name('updateUserCrew');
        Route::delete('/crew/{user}', [UserController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN')->name('deleteUserCrew');
    });

    //Payment
    Route::get('/payment', [PaymentMethodController::class, 'index'])->middleware('auth:api')->name('payment');
    Route::post('/payment', [PaymentMethodController::class, 'store'])->middleware('auth:api')->name('addPayment');
    Route::get('/payment/{payment}', [PaymentMethodController::class, 'show'])->middleware('auth:api')->name('showPayment');
    Route::put('/payment/{payment}', [PaymentMethodController::class, 'update'])->middleware('auth:api')->name('updatePayment');
    Route::delete('/epayment/{payment}', [PaymentMethodController::class, 'destroy'])->middleware('auth:api')->name('deletePayment');

    //Menu Category
    Route::get('/category', [MenuCategoriesController::class, 'index'])->middleware('auth:api')->name('category');
    Route::post('/category', [MenuCategoriesController::class, 'store'])->middleware('auth:api', 'role:ADMIN')->name('addCategory');
    Route::get('/category/{category}', [MenuCategoriesController::class, 'show'])->middleware('auth:api')->name('showCategory');
    Route::put('/category/{category}', [MenuCategoriesController::class, 'update'])->middleware('auth:api', 'role:ADMIN')->name('updateCategory');
    Route::delete('/category/{category}', [MenuCategoriesController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN')->name('deleteCategory');

    //Menu
    Route::get('/menu', [MenuController::class, 'index'])->middleware('auth:api')->name('menu');
    Route::post('/menu', [MenuController::class, 'store'])->middleware('auth:api', 'role:ADMIN')->name('addMenu');
    Route::get('/menu/{menu}', [MenuController::class, 'show'])->middleware('auth:api')->name('showMenu');
    Route::put('/menu/{menu}', [MenuController::class, 'update'])->middleware('auth:api', 'role:ADMIN')->name('updateMenu');
    Route::delete('/menu/{menu}', [MenuController::class, 'destroy'])->middleware('auth:api', 'role:ADMIN')->name('deleteMenu');

    //Menu Tenant
    Route::get('/menu-tenant', [MenuTenantController::class, 'index'])->middleware('auth:api', 'role:TENANT')->name('menuTenant');
    Route::post('/menu-tenant', [MenuTenantController::class, 'store'])->middleware('auth:api', 'role:TENANT')->name('addMenuTenant');
    Route::get('/menu-tenant/{menu}', [MenuTenantController::class, 'show'])->middleware('auth:api', 'role:TENANT')->name('showMenuTenant');
    Route::put('/menu-tenant/{menu}', [MenuTenantController::class, 'update'])->middleware('auth:api', 'role:TENANT')->name('updateMenuTenant');
    Route::delete('/menu-tenant/{menu}', [MenuTenantController::class, 'destroy'])->middleware('auth:api', 'role:TENANT')->name('deleteMenuTenant');

    //Order
    Route::get('/order', [OrderController::class, 'index'])->middleware('auth:api')->name('order');
    Route::post('/order', [OrderController::class, 'store'])->middleware('auth:api')->name('addOrder');
    Route::get('/order/{order}', [OrderController::class, 'show'])->middleware('auth:api')->name('showOrder');
    Route::put('/order/{order}', [OrderController::class, 'update'])->middleware('auth:api')->name('updateOrder');
    Route::delete('/order/{order}', [OrderController::class, 'destroy'])->middleware('auth:api')->name('deleteOrder');

    //Notification
    Route::get('notif', [NotificationController::class, 'notifications']);
    Route::get('notif-unread', [NotificationController::class, 'notificationsUnread']);
    Route::get('notif-read', [NotificationController::class, 'notificationsRead']);

});