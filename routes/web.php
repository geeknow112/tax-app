<?php

use App\Http\Controllers\AccountCategoryController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\DepreciationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\RevenueController;
use Illuminate\Support\Facades\Route;

// 事業体切り替え
Route::post('/entity/switch', [EntityController::class, 'switch'])->name('entity.switch');
Route::resource('entities', EntityController::class);

Route::get('/', [ExpenseController::class, 'index'])->name('expenses.index');
Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
Route::post('/expenses/{expense}/classify', [ExpenseController::class, 'classify'])->name('expenses.classify');
Route::get('/expenses/search-prev', [ExpenseController::class, 'searchPrevYear'])->name('expenses.searchPrev');
Route::post('/expenses/bulk-classify', [ExpenseController::class, 'bulkClassify'])->name('expenses.bulkClassify');

Route::get('/import', [ImportController::class, 'show'])->name('import.show');
Route::post('/import', [ImportController::class, 'store'])->name('import.store');

Route::get('/pl', [ProfitLossController::class, 'index'])->name('pl.index');
Route::get('/bs', [BalanceSheetController::class, 'index'])->name('bs.index');

Route::get('/revenues', [RevenueController::class, 'index'])->name('revenues.index');
Route::post('/revenues', [RevenueController::class, 'store'])->name('revenues.store');
Route::delete('/revenues/{revenue}', [RevenueController::class, 'destroy'])->name('revenues.destroy');

Route::get('/depreciations', [DepreciationController::class, 'index'])->name('depreciations.index');
Route::post('/depreciations', [DepreciationController::class, 'store'])->name('depreciations.store');
Route::delete('/depreciations/{depreciation}', [DepreciationController::class, 'destroy'])->name('depreciations.destroy');

Route::get('/categories', [AccountCategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [AccountCategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [AccountCategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [AccountCategoryController::class, 'destroy'])->name('categories.destroy');
Route::post('/categories/reorder', [AccountCategoryController::class, 'reorder'])->name('categories.reorder');


Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
Route::post('/documents/{document}/items', [DocumentController::class, 'addItem'])->name('documents.addItem');
Route::delete('/documents/{document}/items/{item}', [DocumentController::class, 'removeItem'])->name('documents.removeItem');
Route::post('/documents/{document}/status', [DocumentController::class, 'updateStatus'])->name('documents.updateStatus');
