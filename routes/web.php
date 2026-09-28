<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->role === 'admin' ? 'staff.index' : 'pos.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/orders/{order}/check', [PosController::class, 'check'])->middleware('role:cashier,admin')->name('pos.orders.check');
    Route::get('/staff', [StaffController::class, 'index'])->middleware('role:manager,admin')->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->middleware('role:admin')->name('staff.store');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->middleware('role:manager,admin')->name('staff.update');
    Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->middleware('role:admin')->name('staff.destroy');
    Route::middleware('role:admin')->prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');
        Route::post('/categories', [CatalogController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [CatalogController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [CatalogController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/menu-items', [CatalogController::class, 'storeMenuItem'])->name('menu-items.store');
        Route::put('/menu-items/{menuItem}', [CatalogController::class, 'updateMenuItem'])->name('menu-items.update');
        Route::delete('/menu-items/{menuItem}', [CatalogController::class, 'destroyMenuItem'])->name('menu-items.destroy');
        Route::post('/tables', [CatalogController::class, 'storeTable'])->name('tables.store');
        Route::put('/tables/{diningTable}', [CatalogController::class, 'updateTable'])->name('tables.update');
        Route::delete('/tables/{diningTable}', [CatalogController::class, 'destroyTable'])->name('tables.destroy');
    });
    Route::get('/pos/orders/{order}/edit', [PosController::class, 'edit'])
        ->middleware('role:cashier,admin')->name('pos.orders.edit');
    Route::put('/pos/orders/{order}', [PosController::class, 'update'])
        ->middleware('role:cashier,admin')->name('pos.orders.update');
    Route::post('/pos/orders/{order}/cancel', [PosController::class, 'cancel'])
        ->middleware('role:cashier,admin')->name('pos.orders.cancel');
    Route::post('/pos/orders', [PosController::class, 'store'])
        ->middleware('role:cashier,admin')->name('pos.orders.store');
    Route::patch('/pos/orders/{order}/status', [PosController::class, 'updateStatus'])
        ->middleware('role:chef,cashier,admin')->name('pos.orders.status');
    Route::post('/pos/orders/{order}/pay', [PosController::class, 'pay'])
        ->middleware('role:cashier,admin')->name('pos.orders.pay');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
